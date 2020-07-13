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
     * All value representations of this resource, organized by property term.
     *
     * <code>
     * array(
     *   {JSON-LD term} => array(
     *     'property' => {property representation},
     *     'alternate_label' => {label},
     *     'alternate_comment' => {comment},
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
    protected $valuesByTemplateProperty;

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
        $thumbnail = null;
        if ($this->thumbnail()) {
            $thumbnail = $this->thumbnail()->getReference();
        }

        return array_merge(
            [
                'o:is_public' => $this->isPublic(),
                'o:owner' => $owner,
                'o:resource_class' => $resourceClass,
                'o:resource_template' => $resourceTemplate,
                'o:thumbnail' => $thumbnail,
                'o:title' => $this->title(),
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
     * Get the thumbnail of this resource.
     *
     * @return Asset
     */
    public function thumbnail()
    {
        return $this->getAdapter('assets')
            ->getRepresentation($this->resource->getThumbnail());
    }

    /**
     * Get the title of this resource.
     *
     * @return string
     */
    public function title()
    {
        $title = $this->resource->getTitle();

        $eventManager = $this->getEventManager();
        $args = $eventManager->prepareArgs(['title' => $title]);
        $eventManager->trigger('rep.resource.title', $this, $args);

        return $args['title'];
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
     * Get all value representations of this resource, by term or template row.
     *
     * The two outputs are the same when there are no duplicated property in the
     * template. The key for template row are "term" for the first property, then
     * "term-ResourceTemplateProperty position" when the property is duplicated.
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
     *       {…},
     *     ),
     *   ),
     * )
     * </code>
     *
     * @param $byTemplateProperty
     * @return array
     */
    public function values($byTemplateProperty = false)
    {
        if (isset($this->values)) {
            return $byTemplateProperty
                ? $this->valuesByTemplateProperty
                : $this->values;
        }

        $values = [];
        $valuesByTemplateProperty = [];
        $dataTypesByProperty = [];
        $hasDuplicate = false;

        // Set the default template info one time.
        $template = $this->resourceTemplate();
        if ($template) {
            foreach ($template->resourceTemplateProperties() as $templateProperty) {
                $property = $templateProperty->property();
                $term = $property->term();
                $dataTypes = $templateProperty->dataTypes();
                $keyTemplateProperty = $term . '-' . $templateProperty->position();
                // With duplicate properties, keep only the first label and
                // comment.
                if (isset($values[$term])) {
                    $hasDuplicate = true;
                    $valuesByTemplateProperty[$keyTemplateProperty] = [
                        'property' => $property,
                        'alternate_label' => $templateProperty->alternateLabel(),
                        'alternate_comment' => $templateProperty->alternateComment(),
                    ];
                    $dataTypesByProperty[$term] += array_fill_keys($dataTypes, $keyTemplateProperty);
                    continue;
                }
                $values[$term] = [
                    'property' => $property,
                    'alternate_label' => $templateProperty->alternateLabel(),
                    'alternate_comment' => $templateProperty->alternateComment(),
                ];
                $valuesByTemplateProperty[$term] = $values[$term];
                $dataTypesByProperty[$term] = array_fill_keys($dataTypes, $term);
            }
        } else {
            // Force prepend title and description when there is no template.
            $values = [
                'dcterms:title' => [],
                'dcterms:description' => [],
            ];
        }

        // Get this resource's values.
        foreach ($this->resource->getValues() as $valueEntity) {
            $value = new ValueRepresentation($valueEntity, $this->getServiceLocator());
            if ($value->isHidden()) {
                // Skip this resource value if the resource is not available
                // (most likely because it is private).
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

        // Remove terms without values.
        $removeEmpty = function ($v) {
            return !empty($v['values']);
        };
        $values = array_filter($values, $removeEmpty);

        $eventManager = $this->getEventManager();
        $args = $eventManager->prepareArgs(['values' => $values]);
        $eventManager->trigger('rep.resource.values', $this, $args);

        $this->values = $args['values'];

        // Prepare the list for template with duplicated properties after the
        // event above.
        // Note: duplicated properties don't have duplicated data types, so
        // values can be remapped directly.
        if ($template && $hasDuplicate) {
            foreach ($this->values as $term => $data) {
                foreach ($data['values'] as $value) {
                    $dataType = $value->type();
                    $keyTemplateProperty = isset($dataTypesByProperty[$term][$dataType])
                        ? $dataTypesByProperty[$term][$dataType]
                        : $term;
                    if (!isset($valuesByTemplateProperty[$keyTemplateProperty]['property'])) {
                        $valuesByTemplateProperty[$keyTemplateProperty]['property'] = $value->property();
                        $valuesByTemplateProperty[$keyTemplateProperty]['alternate_label'] = null;
                        $valuesByTemplateProperty[$keyTemplateProperty]['alternate_comment'] = null;
                    }
                    $valuesByTemplateProperty[$keyTemplateProperty]['values'][] = $value;
                }
            }
            // Remove keys without values.
            $this->valuesByTemplateProperty = array_filter($valuesByTemplateProperty, $removeEmpty);
        } else {
            $this->valuesByTemplateProperty = $this->values;
        }

        return $byTemplateProperty
            ? $this->valuesByTemplateProperty
            : $this->values;
    }

    /**
     * Get value representations.
     *
     * @param string $term The prefix:local_part
     * @param array $options
     * - type: (null) Get values of this type only. Default types are "literal",
     *   "uri", and "resource". Returns all types by default.
     * - all: (false) If true, returns all values that match criteria. If false,
     *   returns the first matching value.
     * - default: (null) Default value if no values match criteria. Returns null
     *   by default for single result, empty array for all results.
     * - lang: (null) Get values of this language only. Returns values of all
     *   languages by default.
     * @return ValueRepresentation|ValueRepresentation[]|mixed
     */
    public function value($term, array $options = [])
    {
        // Set defaults.
        $options += [
            'type' => null,
            'all' => false,
            'default' => isset($options['all']) ? [] : null,
            'lang' => null,
        ];

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
        foreach ($this->values() as $property) {
            foreach ($property['values'] as $value) {
                if (strtok($value->type(), ':') === 'resource') {
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
     * + viewName: Name of view script, or a view model. Default
     *   "common/resource-values"
     *
     * @param array $options
     * @return string
     */
    public function displayValues(array $options = [])
    {
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
        $title = $this->title();
        if (null !== $title) {
            return $title;
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
        $template = $this->resourceTemplate();
        if ($template && $template->descriptionProperty()) {
            $description = $this->value($template->descriptionProperty()->term());
            if (null !== $description) {
                return $description;
            }
        }
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
