<?php
namespace Omeka\Api\Representation;

use Omeka\Api\Exception;
use Omeka\Api\Representation\ResourceClassRepresentation;
use Omeka\Api\Representation\ValueRepresentation;
use Omeka\Entity\Resource;
use Omeka\Entity\Value;
use Omeka\Entity\Vocabulary;

/**
 * Abstract resource entity representation.
 *
 * Provides functionality for entities that extend Omeka\Entity\Resource.
 */
abstract class AbstractResourceEntityRepresentation extends AbstractEntityRepresentation
{
    /**
     * All value representations of this resource, organized by property.
     *
     * <code>
     * array(
     *   {JSON-LD term} => array(
     *     'property' => {property representation},
     *     'values' => {
     *       {value representation},
     *       {value representation},
     *       {...},
     *     },
     *   ),
     * )
     * </code>
     *
     * @var array
     */
    protected $values;

    /**
     * Get the internal members of this resource entity.
     *
     * @return array
     */
    abstract function getResourceJsonLd();

    /**
     * {@inheritDoc}
     */
    public function getJsonLd()
    {
        // Set the JSON-LD node type.
        $nodeType = array();
        $resourceClass = $this->resourceClass();
        if ($resourceClass) {
            $this->addVocabularyToContext($resourceClass->vocabulary());
            $nodeType['@type'] = $resourceClass->term();
        }

        // Set the date time value objects.
        $dateTime = array(
            'o:created' => array(
                '@value' => $this->getDateTime($this->getData()->getCreated()),
                '@type' => 'http://www.w3.org/2001/XMLSchema#dateTime',
            ),
            'o:modified' => null,
        );
        if ($this->getData()->getModified()) {
            $dateTime['o:modified'] = array(
               '@value' => $this->getDateTime($this->getData()->getModified()),
               '@type' => 'http://www.w3.org/2001/XMLSchema#dateTime',
            );
        }

        // Set the values as JSON-LD value objects.
        $values = array();
        foreach ($this->values() as $term => $property) {
            $this->addVocabularyToContext($property['property']->vocabulary());
            foreach ($property['values'] as $value) {
                $values[$term][] = $value;
            }
        }

        return array_merge(
            $nodeType,
            array(
                'o:is_public' => $this->isPublic(),
                'o:owner' => $this->getReference(
                    null,
                    $this->getData()->getOwner(),
                    $this->getAdapter('users')
                ),
                'o:resource_class' => $this->getReference(
                    null,
                    $this->getData()->getResourceClass(),
                    $this->getAdapter('resource_classes')
                ),
                'o:resource_template' => $this->getReference(
                    null,
                    $this->getData()->getResourceTemplate(),
                    $this->getAdapter('resource_templates')
                ),
            ),
            $dateTime,
            $this->getResourceJsonLd(),
            $values
        );
    }

    /**
     * Get the resource name of the corresponding entity API adapter.
     *
     * @return string
     */
    public function resourceName()
    {
        return $this->getData()->getResourceName();
    }

    /**
     * Get the resource class representation of this resource.
     *
     * @return ResourceClassRepresentation
     */
    public function resourceClass()
    {
        return $this->getAdapter('resource_classes')
            ->getRepresentation(null, $this->getData()->getResourceClass());
    }
    
    /**
     * Get the resource template of this resource.
     * 
     * @return ResourceTemplateRepresentation
     */
    public function resourceTemplate()
    {
        return $this->getAdapter('resource_templates')
            ->getRepresentation(null, $this->getData()->getResourceTemplate());
    }

    /**
     * Get the owner representation of this resource.
     *
     * @return UserRepresentation
     */
    public function owner()
    {
        return $this->getAdapter('users')
            ->getRepresentation(null, $this->getData()->getOwner());
    }

    /**
     * Get whether this resource is public or not public.
     *
     * @return bool
     */
    public function isPublic()
    {
        return $this->getData()->isPublic();
    }

    /**
     * Get the date-time when this resource was created.
     *
     * @return DateTime
     */
    public function created()
    {
        return $this->getData()->getCreated();
    }

    /**
     * Get the date-time when this resource was last modified.
     *
     * @return DateTime
     */
    public function modified()
    {
        return $this->getData()->getModified();
    }

    /**
     * Get all value representations of this resource.
     *
     * <code>
     * array(
     *   {term} => array(
     *     'property' => {PropertyRepresentation},
     *     'alternate_label' => {label},
     *     'alternate_comment' => {comment},
     *     'values' => array(
     *       {ValueRepresentation},
     *       {ValueRepresentation},
     *     ),
     *   ),
     * )
     * </code>
     *
     * @return array
     */
    public function values()
    {
        if (isset($this->values)) {
            return $this->values;
        }

        // Set the default template info.
        $templateInfo = array(
            'dcterms:title' => array(),
            'dcterms:description' => array(),
        );

        $template = $this->resourceTemplate();
        if ($template) {
            // Set the custom template info.
            $templateInfo = array();
            foreach ($template->resourceTemplateProperties() as $templateProperty) {
                $term = $templateProperty->property()->term();
                $templateInfo[$term] = array(
                    'alternate_label' => $templateProperty->alternateLabel(),
                    'alternate_comment' => $templateProperty->alternateComment(),
                );
            }
        }

        // Get this resource's values.
        $values = array();
        foreach ($this->getData()->getValues() as $valueEntity) {
            $value = new ValueRepresentation($valueEntity, $this->getServiceLocator());
            if ('resource' === $value->type() && null === $value->valueResource()) {
                // Skip this resource value if the resource is not available
                // (most likely becuase it is private).
                continue;
            }
            $term = $value->property()->term();
            if (!isset($values[$term]['property'])) {
                $values[$term]['property'] = $value->property();
                $values[$term]['alternate_label'] = null;
                $values[$term]['alternate_comment'] = null;
            }
            $values[$term]['values'][] = $value;
        }

        // Order this resource's values according to the template order.
        $sortedValues = array();
        foreach ($values as $term => $valueInfo) {
            foreach ($templateInfo as $templateTerm => $templateAlternates) {
                if (array_key_exists($templateTerm, $values)) {
                    $sortedValues[$templateTerm] =
                        array_merge($values[$templateTerm], $templateAlternates);
                }
            }
        }

        $this->values = $sortedValues + $values;
        return $this->values;
    }

    /**
     * Get value representations.
     *
     * @param string $term The prefix:local_part
     * @param array $options
     * - type: (null) Get values of this type only. Valid types are "literal",
     *   "uri", and "resource". Returns all types by default.
     * - all: (false) If true, returns all values that match criteria. If false,
     *   returns the first matching value.
     * - default: (null) Default value if no values match criteria. Returns null
     *   by default.
     * - lang: (null) Get values of this language only. Returns values of all
     *   languages by default.
     * @return RepresentationInterface|mixed
     */
    public function value($term, array $options = array())
    {
        // Set defaults.
        if (!isset($options['type'])) {
            $options['type'] = null;
        }
        if (!isset($options['all'])) {
            $options['all'] = false;
        }
        if (!isset($options['default'])) {
            $options['default'] = null;
        }
        if (!isset($options['lang'])) {
            $options['lang'] = null;
        }

        if (!$this->getAdapter()->isTerm($term)) {
            return $options['default'];
        }

        if (!isset($this->values()[$term])) {
            return $options['default'];
        }

        // Match only the representations that fit all the criteria.
        $matchingValues = array();
        foreach ($this->values()[$term]['values'] as $value) {
            if (!is_null($options['type'])
                && $value->type() !== $options['type']
            ) {
                continue;
            }
            if (!is_null($options['lang'])
                && $value->lang() !== $options['lang']
            ) {
                continue;
            }
            $matchingValues[] = $value;
        }

        if (!count($matchingValues)) {
            return $options['default'];
        }

        return $options['all'] ? $matchingValues : $matchingValues[0];
    }

    /**
     * Get value representations where this resource is the RDF object.
     *
     * @return array
     */
    public function subjectValues()
    {
        $subjectResourceValues = $this->getAdapter()
            ->getSubjectValues($this->getData());
        $valueRepresentations = array();
        foreach ($subjectResourceValues as $subjectResourceValue) {
            $valueRepresentations[] = new ValueRepresentation(
                $subjectResourceValue,
                $this->getServiceLocator()
            );
        }
        return $valueRepresentations;
    }

    /**
     * Get value representations where this resource is the RDF subject.
     *
     * @return array
     */
    public function objectValues()
    {
        $objectValues = array();
        foreach ($this->values() as $term => $property) {
            foreach ($property['values'] as $value) {
                if ('resource' == $value->type()) {
                    $objectValues[] = $value;
                }
            }
        }
        return $objectValues;
    }

    /**
     * Get the display markup for all values of this resource.
     *
     * Options:
     *
     * + hideVocabulary: Whether to hide vocabulary labels. Default: false
     * + viewName: Name of view script, or a view model. Default
     *   "common/resource-values"
     *
     * @param array $options
     * @return string
     */
    public function displayValues(array $options = array())
    {
        if (!isset($options['hideVocabulary'])) {
            $options['hideVocabulary'] = false;
        }
        if (!isset($options['viewName'])) {
            $options['viewName'] = 'common/resource-values';
        }
        $partial = $this->getViewHelper('partial');
        $options['values'] = $this->values();
        $template = $this->resourceTemplate();
        if ($template) {
            $options['templateProperties'] = $template->resourceTemplateProperties();
        }
        
        return $partial($options['viewName'], $options);
    }

    /**
     * Get the display markup for all values where this resource is the RDF
     * subject or object.
     *
     * Options:
     *
     * + viewName: Name of view script, or a view model. Default
     *   "common/linked-resources"
     *
     * @param array $options
     * @return string
     */
    public function displayLinkedResources(array $options = array())
    {
        if (!isset($options['viewName'])) {
            $options['viewName'] = 'common/linked-resources';
        }
        $partial = $this->getViewHelper('partial');
        $options['subjectValues'] = $this->subjectValues();
        $options['objectValues'] = $this->objectValues();
        return $partial($options['viewName'], $options);
    }

    /**
     * Get the display title for this resource.
     *
     * @param string|null $default
     * @return string|null
     */
    public function displayTitle($default = null)
    {
        $title = $this->value('dcterms:title', array(
            'type' => 'literal',
            'default' => null,
        ));

        if ($title !== null) {
            return (string) $title;
        }

        if ($default === null) {
            $translator = $this->getServiceLocator()->get('MvcTranslator');
            $default = $translator->translate('[Untitled]');
        }

        return $default;
    }

    /**
     * Get the display description for this resource.
     *
     * @param string|null $default
     * @return string|null
     */
    public function displayDescription($default = null)
    {
        return (string) $this->value('dcterms:description', array(
            'type' => 'literal',
            'default' => $default,
        ));
    }

    /**
     * Get the display resource class label for this resource.
     *
     * @param string|null $default
     * @return string|null
     */
    public function displayResourceClassLabel($default = null)
    {
        $resourceClass = $this->resourceClass();
        return $resourceClass ? $resourceClass->label() : $default;
    }

    /**
     * Get the representation of this resource as a value for linking from
     * another resource.
     *
     * @return array
     */
    public function valueRepresentation()
    {
        $representation = array();
        $representation['@id'] = $this->apiUrl();
        $representation['value_resource_id'] = $this->id();
        $representation['value_resource_name'] = $this->resourceName();
        $representation['url'] = $this->url();
        $representation['display_title'] = $this->displayTitle();

        return $representation;
    }

    /**
     * Add a vocabulary term definition to the JSON-LD context.
     *
     * @param VocabularyRepresentation $vocabulary
     */
    protected function addVocabularyToContext(VocabularyRepresentation $vocabulary)
    {
        $this->addTermDefinitionToContext($vocabulary->prefix(), array(
            '@id' => $vocabulary->namespaceUri(),
            'vocabulary_id' => $vocabulary->id(),
            'vocabulary_label' => $vocabulary->label(),
        ));
    }

    /**
     * {@inheritDoc}
     */
    protected function validateData($data)
    {
        if (!$data instanceof Resource) {
            throw new Exception\InvalidArgumentException(
                $this->getTranslator()->translate(sprintf(
                    'Invalid data sent to %s.', get_called_class()
                ))
            );
        }
    }
}
