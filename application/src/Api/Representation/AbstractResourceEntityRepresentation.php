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
        $settings = $this->getServiceLocator()->get('Omeka\Settings');

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
        // According to the JSON-LD spec, the value of the @reverse key "MUST be
        // a JSON object containing members representing reverse properties."
        // Here, we include the key only if the resource has reverse properties.
        $reverse = [];
        if (!$settings->get('disable_jsonld_reverse')) {
            $reverse = $this->subjectValuesForReverse();
            $reverse = $reverse ? ['@reverse' => $reverse] : [];
        }

        return array_merge(
            [
                'o:is_public' => $this->isPublic(),
                'o:owner' => $owner,
                'o:resource_class' => $resourceClass,
                'o:resource_template' => $resourceTemplate,
                'o:thumbnail' => $thumbnail,
                'o:title' => $this->title(),
                'thumbnail_display_urls' => $this->thumbnailDisplayUrls(),
            ],
            $dateTime,
            $this->getResourceJsonLd(),
            $values,
            $reverse
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
     * @return AssetRepresentation
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
     * Get all value representations of this resource by term.
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

        // Order this resource's properties according to the template order.
        $sortedValues = [];
        foreach ($values as $term => $valueInfo) {
            foreach ($templateInfo as $templateTerm => $templateAlternates) {
                if (array_key_exists($templateTerm, $values)) {
                    $sortedValues[$templateTerm] =
                        array_merge($values[$templateTerm], $templateAlternates);
                }
            }
        }

        $values = $sortedValues + $values;

        $eventManager = $this->getEventManager();
        $args = $eventManager->prepareArgs(['values' => $values]);
        $eventManager->trigger('rep.resource.values', $this, $args);

        $this->values = $args['values'];
        return $this->values;
    }

    /**
     * Get value representations.
     *
     * @param string $term The prefix:local_part
     * @param array $options
     * - type (array|string): Get values of these types only. Default types are
     *   "literal", "uri", "resource", "resource:item", "resource:media" and
     *   "resource:itemset". Returns all types by default.
     * - all: (false) If true, returns all values that match criteria. If false,
     *   returns the first matching value.
     * - default: (null) Default value if no values match criteria. Returns null
     *   by default for single result, empty array for all results.
     * - lang (array|string): Get values of these languages only. Returns values
     *   of all languages by default. Use `['']` to get values without language.
     * @return ValueRepresentation|ValueRepresentation[]|mixed
     */
    public function value($term, array $options = [])
    {
        // Set defaults.
        if (!isset($options['all'])) {
            $options['all'] = false;
        }
        if (!isset($options['default'])) {
            $options['default'] = $options['all'] ? [] : null;
        }

        if (!$this->getAdapter()->isTerm($term)) {
            return $options['default'];
        }

        if (!isset($this->values()[$term])) {
            return $options['default'];
        }

        if (empty($options['type'])) {
            $types = false;
        } elseif (is_array($options['type'])) {
            $types = array_fill_keys(array_map('strtolower', $options['type']), true);
        } else {
            $types = [strtolower($options['type']) => true];
        }

        if (empty($options['lang'])) {
            $langs = false;
        } elseif (is_array($options['lang'])) {
            $langs = array_fill_keys(array_map('strtolower', $options['lang']), true);
        } else {
            $langs = [strtolower($options['lang']) => true];
        }

        // Match only the representations that fit all the criteria.
        $matchingValues = [];
        foreach ($this->values()[$term]['values'] as $value) {
            if ($types && empty($types[strtolower($value->type())])) {
                continue;
            }
            if ($langs && empty($langs[strtolower((string) $value->lang())])) {
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
     * @param int|string|null $propertyId Filter by property ID
     * @param string|null $resourceType Filter by resource type
     * @param int|null $siteId Filter by site ID
     * @return array
     */
    public function subjectValues($page = null, $perPage = null, $propertyId = null, $resourceType = null, $siteId = null)
    {
        $results = $this->getAdapter()->getSubjectValues($this->resource, $page, $perPage, $propertyId, $resourceType, $siteId);
        $subjectValues = [];
        foreach ($results as $result) {
            $index = $result['property_alternate_label'] ?: $result['property_label'];
            $result['val'] = new ValueRepresentation($result['val'], $this->getServiceLocator());
            $subjectValues[$index][] = $result;
        }
        return $subjectValues;
    }

    /**
     * Get the subject values for the JSON-LD @reverse array.
     *
     * @see https://w3c.github.io/json-ld-syntax/#reverse-properties
     * @param int|string|null $propertyId Filter by property ID
     * @param string|null $resourceType Filter by resource type
     * @param int|null $siteId Filter by site ID
     * @return array
     */
    public function subjectValuesForReverse($propertyId = null, $resourceType = null, $siteId = null)
    {
        $url = $this->getViewHelper('Url');
        $subjectValuesSimple = $this->getAdapter()->getSubjectValuesSimple($this->resource, $propertyId, $resourceType, $siteId);
        $subjectValues = [];
        foreach ($subjectValuesSimple as $subjectValue) {
            $subjectValues[$subjectValue['term']][] = [
                '@id' => $url('api/default', ['resource' => 'resources', 'id' => $subjectValue['id']], ['force_canonical' => true]),
                'o:title' => $subjectValue['title'],
            ];
        }
        return $subjectValues;
    }

    /**
     * Get the total count of this resource's subject values.
     *
     * @param int|string|null $propertyId Filter by property ID
     * @param string|null $resourceType Filter by resource type
     * @param int|null $siteId Filter by site ID
     * @return int
     */
    public function subjectValueTotalCount($propertyId = null, $resourceType = null, $siteId = null)
    {
        return $this->getAdapter()->getSubjectValueTotalCount($this->resource, $propertyId, $resourceType, $siteId);
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
     * - viewName: Name of view script, or a view model. Default "common/resource-values"
     * - siteId: A site ID
     *
     * @param array $options
     * @return string
     */
    public function displayValues(array $options = [])
    {
        $options['viewName'] ??= 'common/resource-values';
        $options['siteId'] ??= null;

        $services = $this->getServiceLocator();
        $values = $this->values();

        if ($options['siteId']) {
            // Exclude resources that are not assigned to the site if the
            // "exclude_resources_not_in_site" site setting is true.
            foreach ($values as $term => $propertyData) {
                foreach ($propertyData['values'] as $valueIndex => $value) {
                    $valueResource = $value->valueResource();
                    if ($valueResource && in_array($valueResource->resourceName(), ['items', 'item_sets'])) {
                        $resourceSites = $valueResource->sites();
                        if (!isset($resourceSites[$options['siteId']])) {
                            // This item is not assigned to the current site, so remove it.
                            unset($values[$term]['values'][$valueIndex]);
                        }
                    }
                }
                if (!$values[$term]['values']) {
                    // This property no longer has values, so remove it.
                    unset($values[$term]);
                }
            }
        }

        $eventManager = $this->getEventManager();
        $args = $eventManager->prepareArgs(['values' => $values]);
        $eventManager->trigger('rep.resource.display_values', $this, $args);

        $template = $this->resourceTemplate();
        $options['resource'] = $this;
        $options['values'] = $args['values'];
        $options['templateProperties'] = $template
            ? $template->resourceTemplateProperties()
            : [];

        $partial = $this->getViewHelper('partial');
        return $partial($options['viewName'], $options);
    }

    /**
     * Get the display markup for values where this resource is the RDF object.
     *
     * Options:
     *
     * - viewName: Name of view script, or a view model. Default "common/linked-resources"
     * - page: The page number
     * - perPage: The number of resources per page
     * - resourceProperty: Compound identifier with the pattern: <resource_type>:<property_id>
     * - siteId: A site ID
     *
     * For resourceProperty, the <resource_type> can be items, item_sets, media.
     * The <property_id> should follow the pattern laid out in
     * AbstractResourceEntityAdapter::getSubjectValuesQueryBuilder(). If a
     * $resourceProperty isn't passed or is invalid, the default is all
     * properties for the current resource type.
     *
     * @param array $options
     * @return string
     */
    public function displaySubjectValues(array $options = [])
    {
        $services = $this->getServiceLocator();
        $adapter = $this->getAdapter();

        $viewName = $options['viewName'] ?? 'common/linked-resources';
        $page = $options['page'] ?? null;
        $perPage = $options['perPage'] ?? null;
        $siteId = $options['siteId'] ?? null;

        $subjectValuePropertiesItems = $adapter->getSubjectValueProperties($this->resource, 'items', $siteId);
        $subjectValuePropertiesItemSets = $adapter->getSubjectValueProperties($this->resource, 'item_sets', $siteId);
        $subjectValuePropertiesMedia = $adapter->getSubjectValueProperties($this->resource, 'media', $siteId);

        if (!$subjectValuePropertiesItems && !$subjectValuePropertiesItemSets && !$subjectValuePropertiesMedia) {
            // This resource has no subject values;
            return null;
        }

        $resourcePropertiesAll = [
            'items' => $subjectValuePropertiesItems,
            'item_sets' => $subjectValuePropertiesItemSets,
            'media' => $subjectValuePropertiesMedia,
        ];
        // Find the default resource property by detecting the first resource
        // type that has properties.
        $defaultResourceProperty = null;
        foreach ($resourcePropertiesAll as $resourceType => $resourceProperties) {
            if ($resourceProperties) {
                $defaultResourceProperty = sprintf('%s:', $resourceType);
                break;
            }
        }
        $resourceProperty = $options['resourceProperty'] ?? $defaultResourceProperty;

        $resourceType = $adapter->getResourceName();
        $propertyId = null;
        if ($resourceProperty && false !== strpos($resourceProperty, ':')) {
            // Derive the resource type and property ID from $resourceProperty.
            [$resourceType, $propertyId] = explode(':', $resourceProperty);
        }

        $totalCount = $adapter->getSubjectValueTotalCount($this->resource, $propertyId, $resourceType, $siteId);
        $subjectValues = $this->subjectValues($page, $perPage, $propertyId, $resourceType, $siteId);

        $partial = $this->getViewHelper('partial');
        return $partial($viewName, [
            'objectResource' => $this,
            'subjectValues' => $subjectValues,
            'page' => $page,
            'perPage' => $perPage,
            'totalCount' => $totalCount,
            'resourceProperty' => $resourceProperty,
            'propertyId' => $propertyId,
            'resourceType' => $resourceType,
            'resourcePropertiesAll' => $resourcePropertiesAll,
        ]);
    }

    /**
     * Get the display title for this resource.
     *
     * @param string|null $default
     * @param array|string|null $lang
     * @return string|null
     */
    public function displayTitle($default = null, $lang = null)
    {
        $title = null;
        $template = $this->resourceTemplate();
        if ($template && $template->titleProperty()) {
            $titleTerm = $template->titleProperty()->term();
        } else {
            $titleTerm = 'dcterms:title';
        }

        if ($lang !== null) {
            if ($titleValue = $this->value($titleTerm, ['lang' => $lang])) {
                $title = (string) $titleValue->value();
            }
        }

        if ($title === null) {
            $title = $this->title();
        }

        if ($title === null) {
            if ($default === null) {
                $translator = $this->getServiceLocator()->get('MvcTranslator');
                $title = $translator->translate('[Untitled]');
            } else {
                $title = $default;
            }
        }

        $eventManager = $this->getEventManager();
        $args = $eventManager->prepareArgs(['title' => $title]);
        $eventManager->trigger('rep.resource.display_title', $this, $args);

        return $args['title'];
    }

    /**
     * Get the display description for this resource.
     *
     * @param string|null $default
     * @param array|string|null $lang
     * @return string|null
     */
    public function displayDescription($default = null, $lang = null)
    {
        $description = null;
        $template = $this->resourceTemplate();
        if ($template && $template->descriptionProperty()) {
            $descriptionTerm = $template->descriptionProperty()->term();
        } else {
            $descriptionTerm = 'dcterms:description';
        }

        if ($lang !== null) {
            if ($descriptionValue = $this->value($descriptionTerm, ['default' => $default, 'lang' => $lang])) {
                $description = (string) $descriptionValue->value();
            }
        }

        if ($description === null) {
            $description = (string) $this->value($descriptionTerm, ['default' => $default]);
        }

        $eventManager = $this->getEventManager();
        $args = $eventManager->prepareArgs(['description' => $description]);
        $eventManager->trigger('rep.resource.display_description', $this, $args);
        return $args['description'];
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
     * Get the display resource template label for this resource.
     *
     * @param string|null $default
     * @return string|null
     */
    public function displayResourceTemplateLabel($default = null)
    {
        $resourceTemplate = $this->resourceTemplate();
        return $resourceTemplate ? $resourceTemplate->label() : $default;
    }

    /**
     * Get a "pretty" link to this resource containing a thumbnail and
     * display title.
     *
     * @param string $thumbnailType Type of thumbnail to show
     * @param string|null $titleDefault See $default param for displayTitle()
     * @param string|null $action Action to link to (see link() and linkRaw())
     * @param array $attributes HTML attributes, key and value
     * @param array|string|null $lang Language IETF tag
     * @return string
     */
    public function linkPretty(
        $thumbnailType = 'square',
        $titleDefault = null,
        $action = null,
        array $attributes = null,
        $lang = null
    ) {
        $escape = $this->getViewHelper('escapeHtml');
        $thumbnail = $this->getViewHelper('thumbnail');
        $linkContent = sprintf(
            '%s<span class="resource-name">%s</span>',
            $thumbnail($this, $thumbnailType),
            $escape($this->displayTitle($titleDefault, $lang))
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
