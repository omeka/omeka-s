<?php
namespace Omeka\Api\Representation;

use Omeka\Api\Adapter\AdapterInterface;
use Omeka\Entity\Resource;

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
     *       {…},
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
    abstract public function getResourceJsonLd();

    /**
     * Get the JSON-LD type for this specific kind of resource.
     *
     * @return string
     */
    abstract public function getResourceJsonLdType();

    public function __construct(Resource $resource, AdapterInterface $adapter)
    {
        parent::__construct($resource, $adapter);
    }

    /**
     * {@inheritDoc}
     */
    public function getJsonLdType()
    {
        $type = $this->getResourceJsonLdType();

        $resourceClass = $this->resourceClass();
        if ($resourceClass) {
            $type = (array) $type;
            $type[] = $resourceClass->term();
        }

        return $type;
    }

    /**
     * {@inheritDoc}
     */
    public function getJsonLd()
    {
        // Set the date time value objects.
        $dateTime = [
            'o:created' => [
                '@value' => $this->getDateTime($this->created()),
                '@type' => 'http://www.w3.org/2001/XMLSchema#dateTime',
            ],
            'o:modified' => null,
        ];
        if ($this->modified()) {
            $dateTime['o:modified'] = [
               '@value' => $this->getDateTime($this->modified()),
               '@type' => 'http://www.w3.org/2001/XMLSchema#dateTime',
            ];
        }

        // Set the values as JSON-LD value objects.
        $values = [];
        foreach ($this->values() as $term => $property) {
            foreach ($property['values'] as $value) {
                $values[$term][] = $value;
            }
        }

        $owner = null;
        if ($this->owner()) {
            $owner = $this->owner()->getReference();
        }
        $resourceClass = null;
        if ($this->resourceClass()) {
            $resourceClass = $this->resourceClass()->getReference();
        }
        $resourceTemplate = null;
        if ($this->resourceTemplate()) {
            $resourceTemplate = $this->resourceTemplate()->getReference();
        }

        return array_merge(
            [
                'o:is_public' => $this->isPublic(),
                'o:owner' => $owner,
                'o:resource_class' => $resourceClass,
                'o:resource_template' => $resourceTemplate,
            ],
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
        return $this->resource->getResourceName();
    }

    /**
     * Get the resource class representation of this resource.
     *
     * @return ResourceClassRepresentation
     */
    public function resourceClass()
    {
        return $this->getAdapter('resource_classes')
            ->getRepresentation($this->resource->getResourceClass());
    }

    /**
     * Get the resource template of this resource.
     *
     * @return ResourceTemplateRepresentation
     */
    public function resourceTemplate()
    {
        return $this->getAdapter('resource_templates')
            ->getRepresentation($this->resource->getResourceTemplate());
    }

    /**
     * Get the owner representation of this resource.
     *
     * @return UserRepresentation
     */
    public function owner()
    {
        return $this->getAdapter('users')
            ->getRepresentation($this->resource->getOwner());
    }

    /**
     * Get whether this resource is public or not public.
     *
     * @return bool
     */
    public function isPublic()
    {
        return $this->resource->isPublic();
    }

    /**
     * Get the date-time when this resource was created.
     *
     * @return \DateTime
     */
    public function created()
    {
        return $this->resource->getCreated();
    }

    /**
     * Get the date-time when this resource was last modified.
     *
     * @return \DateTime
     */
    public function modified()
    {
        return $this->resource->getModified();
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
        $templateInfo = [
            'dcterms:title' => [],
            'dcterms:description' => [],
        ];

        $template = $this->resourceTemplate();
        if ($template) {
            // Set the custom template info.
            $templateInfo = [];
            foreach ($template->resourceTemplateProperties() as $templateProperty) {
                $term = $templateProperty->property()->term();
                $templateInfo[$term] = [
                    'alternate_label' => $templateProperty->alternateLabel(),
                    'alternate_comment' => $templateProperty->alternateComment(),
                ];
            }
        }

        // Get this resource's values.
        $values = [];
        foreach ($this->resource->getValues() as $valueEntity) {
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
        $sortedValues = [];
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
    public function value($term, array $options = [])
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
        $matchingValues = [];
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
     * @param int $page
     * @param int $perPage
     * @param int $property Filter by property ID
     * @return array
     */
    public function subjectValues($page = null, $perPage = null, $property = null)
    {
        $values = $this->getAdapter()->getSubjectValues($this->resource, $page, $perPage, $property);
        $subjectValues = [];
        foreach ($values as $value) {
            $valueRep = new ValueRepresentation($value, $this->getServiceLocator());
            $subjectValues[$valueRep->property()->term()][] = $valueRep;
        }
        return $subjectValues;
    }

    /**
     * Get the total count of this resource's subject values.
     *
     * @param int $property Filter by property ID
     * @return int
     */
    public function subjectValueTotalCount($property = null)
    {
        return $this->getAdapter()->getSubjectValueTotalCount($this->resource, $property);
    }

    /**
     * Get distinct properties (predicates) where this resource is the RDF object.
     *
     * @return array
     */
    public function subjectValueProperties()
    {
        $propertyAdapter = $this->getAdapter('properties');
        $properties = $this->getAdapter()->getSubjectValueProperties($this->resource);
        $subjectProperties = [];
        foreach ($properties as $property) {
            $subjectProperties[] = $propertyAdapter->getRepresentation($property);
        }
        return $subjectProperties;
    }

    /**
     * Get value representations where this resource is the RDF subject.
     *
     * @return array
     */
    public function objectValues()
    {
        $objectValues = [];
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
    public function displayValues(array $options = [])
    {
        if (!isset($options['hideVocabulary'])) {
            $options['hideVocabulary'] = false;
        }
        if (!isset($options['viewName'])) {
            $options['viewName'] = 'common/resource-values';
        }
        $partial = $this->getViewHelper('partial');

        $eventManager = $this->getEventManager();
        $args = $eventManager->prepareArgs(['values' => $this->values()]);
        $eventManager->trigger('rep.resource.display_values', $this, $args);
        $options['values'] = $args['values'];

        $template = $this->resourceTemplate();
        if ($template) {
            $options['templateProperties'] = $template->resourceTemplateProperties();
        }

        return $partial($options['viewName'], $options);
    }

    /**
     * Get the display markup for values where this resource is the RDF object.
     *
     * @param int $page
     * @param int $perPage
     * @param int $property Filter by property ID
     * @return string
     */
    public function displaySubjectValues($page = null, $perPage = null, $property = null)
    {
        $subjectValues = $this->subjectValues($page, $perPage, $property);
        if (!$subjectValues) {
            return null;
        }
        $partial = $this->getViewHelper('partial');
        return $partial('common/linked-resources', [
            'objectResource' => $this,
            'subjectValues' => $subjectValues,
            'page' => $page,
            'perPage' => $perPage,
            'property' => $property,
            'totalCount' => $this->subjectValueTotalCount($property),
            'properties' => $this->subjectValueProperties(),
        ]);
    }

    /**
     * Get the display title for this resource.
     *
     * @param string|null $default
     * @return string|null
     */
    public function displayTitle($default = null)
    {
        $title = $this->value('dcterms:title', [
            'default' => null,
        ]);

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
        return (string) $this->value('dcterms:description', [
            'default' => $default,
        ]);
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
     * Get a "pretty" link to this resource containing a thumbnail and
     * display title.
     *
     * @param string $thumbnailType Type of thumbnail to show
     * @param string|null $titleDefault See $default param for displayTitle()
     * @param string|null $action Action to link to (see link() and linkRaw())
     * @param array $attributes HTML attributes, key and value
     * @return string
     */
    public function linkPretty(
        $thumbnailType = 'square',
        $titleDefault = null,
        $action = null,
        array $attributes = null
    ) {
        $escape = $this->getViewHelper('escapeHtml');
        $thumbnail = $this->getViewHelper('thumbnail');
        $linkContent = sprintf(
            '%s<span class="resource-name">%s</span>',
            $thumbnail($this, $thumbnailType),
            $escape($this->displayTitle($titleDefault))
        );
        if (empty($attributes['class'])) {
            $attributes['class'] = 'resource-link';
        } else {
            $attributes['class'] .= ' resource-link';
        }
        return $this->linkRaw($linkContent, $action, $attributes);
    }

    /**
     * Get the representation of this resource as a value for linking from
     * another resource.
     *
     * @return array
     */
    public function valueRepresentation()
    {
        $representation = [];
        $representation['@id'] = $this->apiUrl();
        $representation['type'] = 'resource';
        $representation['value_resource_id'] = $this->id();
        $representation['value_resource_name'] = $this->resourceName();
        $representation['url'] = $this->url();
        $representation['display_title'] = $this->displayTitle();
        if ($primaryMedia = $this->primaryMedia()) {
            $representation['thumbnail_url'] = $primaryMedia->thumbnailUrl('square');
            $representation['thumbnail_title'] = $primaryMedia->displayTitle();
            $representation['thumbnail_type'] = $primaryMedia->mediaType();
        }

        return $representation;
    }
}
