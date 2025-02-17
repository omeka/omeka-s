<?php declare(strict_types=1);

namespace Common\Stdlib;

use Doctrine\DBAL\Connection;
use Omeka\DataType\Manager as DataTypeManager;

/**
 * @todo Replace by materialized views?
 */
class EasyMeta
{
    const DATA_TYPES_MAIN = [
        'literal' => 'literal',
        'uri' => 'uri',
        'resource' => 'resource',
        'resource:item' => 'resource',
        'resource:itemset' => 'resource',
        'resource:media' => 'resource',
        // Module Annotate (for future usage).
        'resource:annotation' => 'resource',
        'annotation' => 'resource',
        // Module DataTypeGeometry.
        'geography' => 'literal',
        'geography:coordinates' => 'literal',
        'geometry' => 'literal',
        'geometry:coordinates' => 'literal',
        'geometry:position' => 'literal',
        // Module DataTypeGeometry (deprecated).
        'geometry:geography' => 'literal',
        'geometry:geography:coordinates' => 'literal',
        'geometry:geometry' => 'literal',
        'geometry:geometry:coordinates' => 'literal',
        'geometry:geometry:position' => 'literal',
        // Module DataTypePlace.
        'place' => 'uri',
        // Module DataTypeRdf.
        'html' => 'literal',
        'xml' => 'literal',
        'boolean' => 'literal',
        // Module NumericDataTypes.
        'numeric:timestamp' => 'literal',
        'numeric:integer' => 'literal',
        'numeric:duration' => 'literal',
        'numeric:interval' => 'literal',
        // Specific module.
        'email' => 'literal',
    ];

    const RESOURCE_CLASSES = [
        'annotations' => \Annotate\Entity\Annotation::class,
        'assets' => \Omeka\Entity\Asset::class,
        'items' => \Omeka\Entity\Item::class,
        'item_sets' => \Omeka\Entity\ItemSet::class,
        'media' => \Omeka\Entity\Media::class,
        'resources' => \Omeka\Entity\Resource::class,
        'value_annotations' => \Omeka\Entity\ValueAnnotation::class,
        // Common resources that are not base resources.
        'sites' => \Omeka\Entity\Site::class,
        'site_pages' => \Omeka\Entity\SitePage::class,
    ];

    const RESOURCE_RESOURCE_CLASSES = [
        'annotations' => \Annotate\Entity\Annotation::class,
        'items' => \Omeka\Entity\Item::class,
        'item_sets' => \Omeka\Entity\ItemSet::class,
        'media' => \Omeka\Entity\Media::class,
        'resources' => \Omeka\Entity\Resource::class,
        'value_annotations' => \Omeka\Entity\ValueAnnotation::class,
    ];

    const RESOURCE_LABELS = [
        'annotations' => 'annotation', // @translate
        'assets' => 'asset', // @translate
        'items' => 'item', // @translate
        'item_sets' => 'item set', // @translate
        'media' => 'media', // @translate
        'resources' => 'resource', // @translate
        'properties' => 'property', // @translate
        'resource_classes' => 'resource class', // @translate
        'resource_templates' => 'resource template', // @translate
        'vocabularies' => 'vocabulary', // @translate
        'sites' => 'site', // @translate
        'site_pages' => 'page', // @translate
    ];

    const RESOURCE_LABELS_PLURAL = [
        'annotations' => 'annotations', // @translate
        'assets' => 'assets', // @translate
        'items' => 'items', // @translate
        'item_sets' => 'item sets', // @translate
        'media' => 'media', // @translate
        'resources' => 'resources', // @translate
        'properties' => 'properties', // @translate
        'resource_classes' => 'resource classes', // @translate
        'resource_templates' => 'resource templates', // @translate
        'vocabularies' => 'vocabularies', // @translate
        'sites' => 'sites', // @translate
        'site_pages' => 'pages', // @translate
    ];

    const RESOURCE_NAMES = [
        // Resource names.
        'annotations' => 'annotations',
        'assets' => 'assets',
        'items' => 'items',
        'item_sets' => 'item_sets',
        'media' => 'media',
        'resources' => 'resources',
        'value_annotations' => 'value_annotations',
        'sites' => 'sites',
        'site_pages' => 'site_pages',
        // Json-ld type.
        'oa:Annotation' => 'annotations',
        'o:Asset' => 'assets',
        'o:Item' => 'items',
        'o:ItemSet' => 'item_sets',
        'o:Media' => 'media',
        'o:Resource' => 'resources',
        'o:ValueAnnotation' => 'value_annotations',
        'o:Site' => 'sites',
        'o:SitePage' => 'site_pages',
        // Keys in json-ld representation.
        'oa:annotation' => 'annotations',
        'o:asset' => 'assets',
        'o:item' => 'items',
        'o:items' => 'items',
        'o:item_set' => 'item_sets',
        'o:site_item_set' => 'item_sets',
        'o:media' => 'media',
        '@annotations' => 'value_annotations',
        'o:site' => 'sites',
        'o:page' => 'site_pages',
        // Controllers and singular.
        'annotation' => 'annotations',
        'asset' => 'assets',
        'item' => 'items',
        'item-set' => 'item_sets',
        // 'media' => 'media',
        'resource' => 'resources',
        'value-annotation' => 'value_annotations',
        // Value data types.
        'resource:annotation' => 'annotations',
        // 'resource' => 'resources',
        'resource:item' => 'items',
        'resource:itemset' => 'item_sets',
        'resource:media' => 'media',
        'site' => 'sites',
        'page' => 'site_pages',
        'site-page' => 'site_pages',
        // Representation class.
        \Annotate\Api\Representation\AnnotationRepresentation::class => 'annotations',
        \Omeka\Api\Representation\AssetRepresentation::class => 'assets',
        \Omeka\Api\Representation\ItemRepresentation::class => 'items',
        \Omeka\Api\Representation\ItemSetRepresentation::class => 'item_sets',
        \Omeka\Api\Representation\MediaRepresentation::class => 'media',
        \Omeka\Api\Representation\ResourceReference::class => 'resources',
        \Omeka\Api\Representation\ValueAnnotationRepresentation::class => 'value_annotations',
        \Omeka\Api\Representation\SiteRepresentation::class => 'sites',
        \Omeka\Api\Representation\SitePageRepresentation::class => 'site_pages',
        // Entity class.
        \Annotate\Entity\Annotation::class => 'annotations',
        \Omeka\Entity\Asset::class => 'assets',
        \Omeka\Entity\Item::class => 'items',
        \Omeka\Entity\ItemSet::class => 'item_sets',
        \Omeka\Entity\Media::class => 'media',
        \Omeka\Entity\Resource::class => 'resources',
        \Omeka\Entity\ValueAnnotation::class => 'value_annotations',
        \Omeka\Entity\Site::class => 'sites',
        \Omeka\Entity\SitePage::class => 'site_pages',
        // Doctrine entity class (when using get_class() and not getResourceId().
        \DoctrineProxies\__CG__\Annotate\Entity\Annotation::class => 'annotations',
        \DoctrineProxies\__CG__\Omeka\Entity\Asset::class => 'assets',
        \DoctrineProxies\__CG__\Omeka\Entity\Item::class => 'items',
        \DoctrineProxies\__CG__\Omeka\Entity\ItemSet::class => 'item_sets',
        \DoctrineProxies\__CG__\Omeka\Entity\Media::class => 'media',
        // \DoctrineProxies\__CG__\Omeka\Entity\Resource::class => 'resources',
        \DoctrineProxies\__CG__\Omeka\Entity\ValueAnnotation::class => 'value_annotations',
        \DoctrineProxies\__CG__\Omeka\Entity\Site::class => 'sites',
        \DoctrineProxies\__CG__\Omeka\Entity\SitePage::class => 'site_pages',
        // Controllers, in particular in routes.
        'Annotate\Controller\Admin\Annotation' => 'annotations',
        'Annotate\Controller\Admin\AnnotationController' => 'annotations',
        'Omeka\Controller\Admin\Item' => 'items',
        'Omeka\Controller\Admin\ItemController' => 'items',
        'Omeka\Controller\Admin\ItemSet' => 'item_sets',
        'Omeka\Controller\Admin\ItemSetController' => 'item_sets',
        'Omeka\Controller\Admin\Media' => 'media',
        'Omeka\Controller\Admin\MediaController' => 'media',
        'Omeka\Controller\Admin\ValueAnnotation' => 'value_annotations',
        'Omeka\Controller\Admin\ValueAnnotationController' => 'value_annotations',
        'Omeka\Controller\SiteAdmin\Index' => 'sites',
        'Omeka\Controller\SiteAdmin\IndexController' => 'sites',
        'Omeka\Controller\SiteAdmin\Page' => 'site_pages',
        'Omeka\Controller\SiteAdmin\PageController' => 'site_pages',
        'Annotate\Controller\Site\Annotation' => 'annotations',
        'Annotate\Controller\Site\AnnotationController' => 'annotations',
        'Omeka\Controller\Site\Item' => 'items',
        'Omeka\Controller\Site\ItemController' => 'items',
        'Omeka\Controller\Site\ItemSet' => 'item_sets',
        'Omeka\Controller\Site\ItemSetController' => 'item_sets',
        'Omeka\Controller\Site\Media' => 'media',
        'Omeka\Controller\Site\MediaController' => 'media',
        'Omeka\Controller\Site\ValueAnnotation' => 'value_annotations',
        'Omeka\Controller\Site\ValueAnnotationController' => 'value_annotations',
        'Omeka\Controller\Site\Index' => 'sites',
        'Omeka\Controller\Site\IndexController' => 'sites',
        'Omeka\Controller\Site\SitePage' => 'sites_pages',
        'Omeka\Controller\Site\SitePageController' => 'site_pages',
        // Capitalized name found in route __controller__.
        'Annotation' => 'annotations',
        'Item' => 'items',
        'ItemSet' => 'item_sets',
        'Media' => 'media',
        'Resource' => 'resources',
        'ValueAnnotation' => 'value_annotations',
        'Site' => 'sites',
        'SitePage' => 'site_pages',
        'AnnotationController' => 'annotations',
        'ItemController' => 'items',
        'ItemSetController' => 'item_sets',
        'MediaController' => 'media',
        'ResourceController' => 'resources',
        'ValueAnnotationController' => 'value_annotations',
        'SiteController' => 'sites',
        'SitePageController' => 'site_pages',
        // Other deprecated, future or badly written names.
        'o:annotation' => 'annotations',
        'o:Annotation' => 'annotations',
        'o:annotations' => 'annotations',
        'o:assets' => 'assets',
        'resource:items' => 'items',
        'itemset' => 'item_sets',
        'item set' => 'item_sets',
        'item_set' => 'item_sets',
        'itemsets' => 'item_sets',
        'item sets' => 'item_sets',
        'item-sets' => 'item_sets',
        'o:itemset' => 'item_sets',
        'o:item-set' => 'item_sets',
        'o:itemsets' => 'item_sets',
        'o:item-sets' => 'item_sets',
        'o:item_sets' => 'item_sets',
        'resource:itemsets' => 'item_sets',
        'resource:item-set' => 'item_sets',
        'resource:item-sets' => 'item_sets',
        'resource:item_set' => 'item_sets',
        'resource:item_sets' => 'item_sets',
        'o:resource' => 'resources',
        'valueannotation' => 'value_annotations',
        'value annotation' => 'value_annotations',
        'value_annotation' => 'value_annotations',
        'valueannotations' => 'value_annotations',
        'value annotations' => 'value_annotations',
        'value-annotations' => 'value_annotations',
        'o:valueannotation' => 'value_annotations',
        'o:valueannotations' => 'value_annotations',
        'o:value-annotation' => 'value_annotations',
        'o:value-annotations' => 'value_annotations',
        'o:value_annotation' => 'value_annotations',
        'o:value_annotations' => 'value_annotations',
        'resource:valueannotation' => 'value_annotations',
        'resource:valueannotations' => 'value_annotations',
        'resource:value-annotation' => 'value_annotations',
        'resource:value-annotations' => 'value_annotations',
        'resource:value_annotation' => 'value_annotations',
        'resource:value_annotations' => 'value_annotations',
        'site' => 'site_pages',
        'sites' => 'sites',
        'page' => 'site_pages',
        'pages' => 'site_pages',
    ];

    const RESOURCE_TYPES = [
        'annotations' => 'annotation',
        'assets' => 'asset',
        'items' => 'item',
        'item_sets' => 'item-set',
        'media' => 'media',
        'resources' => 'resource',
        'value_annotations' => 'value-annotation',
        'sites' => 'site',
        'site_pages' => 'site-page',
    ];

    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $connection;

    /**
     * @var \Omeka\DataType\Manager
     */
    protected $dataTypeManager;

    /**
     * @var array
     */
    protected static $dataTypesByNames;

    /**
     * @var array
     */
    protected static $dataTypesByNamesUsed;

    /**
     * @var array
     */
    protected static $dataTypeLabelsByNames;

    /**
     * @var array
     */
    protected static $dataTypesMainCustomVocabs;

    /**
     * @var array
     */
    protected static $propertyIdsByTerms;

    /**
     * @var array
     */
    protected static $propertyIdsByTermsAndIds;

    /**
     * @var array
     */
    protected static $propertyIdsByTermsAndIdsUsed;

    /**
     * @var array
     */
    protected static $propertyTermsByTermsAndIds;

    /**
     * @var array
     */
    protected static $propertyLabelsByTerms;

    /**
     * @var array
     */
    protected static $propertyLabelsByTermsAndIds;

    /**
     * @var array
     */
    protected static $resourceClassIdsByTerms;

    /**
     * @var array
     */
    protected static $resourceClassIdsByTermsAndIds;

    /**
     * @var array
     */
    protected static $resourceClassIdsByTermsAndIdsUsed;

    /**
     * @var array
     */
    protected static $resourceClassTermsByTermsAndIds;

    /**
     * @var array
     */
    protected static $resourceClassLabelsByTerms;

    /**
     * @var array
     */
    protected static $resourceClassLabelsByTermsAndIds;

    /**
     * @var array
     */
    protected static $resourceTemplateIdsByLabels;

    /**
     * @var array
     */
    protected static $resourceTemplateIdsByLabelsAndIds;

    /**
     * @var array
     */
    protected static $resourceTemplateIdsByLabelsAndIdsUsed;

    /**
     * @var array
     */
    protected static $resourceTemplateLabelsByLabelsAndIds;

    /**
     * @var array
     */
    protected static $resourceTemplateClassesByIds;

    /**
     * @var array
     */
    protected static $vocabularyIdsByPrefixes;

    /**
     * @var array
     */
    protected static $vocabularyIdsByUris;

    /**
     * @var array
     */
    protected static $vocabularyIdsByPrefixesAndUrisAndIds;

    /**
     * @var array
     */
    protected static $vocabularyLabelsByPrefixesAndUrisAndIds;

    /**
     * @var array
     */
    protected static $vocabularyPrefixesByPrefixesAndUrisAndIds;

    /**
     * @var array
     */
    protected static $vocabularyUrisByPrefixesAndUrisAndIds;

    public function __construct(
        Connection $connection,
        DataTypeManager $dataTypeManager
    ) {
        $this->connection = $connection;
        $this->dataTypeManager = $dataTypeManager;
    }

    public function __invoke(): self
    {
        return $this;
    }

    /**
     * Get the entity class from any class, type or name.
     *
     * For now, only entity classes for resources, asset, site and pages are output.
     *
     * @param string $name
     * @return string|null The entity class if any.
     */
    public function entityClass($name): ?string
    {
        return static::RESOURCE_CLASSES[static::RESOURCE_NAMES[$name] ?? null] ?? null;
    }

    /**
     * Get the entity resource class from any class, type or name.
     *
     * @param string $name
     * @return string|null The entity class of a resource if any.
     */
    public function entityResourceClass($name): ?string
    {
        return static::RESOURCE_RESOURCE_CLASSES[static::RESOURCE_NAMES[$name] ?? null] ?? null;
    }

    /**
     * Get the entity resource classes from any class, type or name or all.
     *
     * @param array|string|null $names
     * @return array The entity classes if any, or all entity classes for
     * resources.
     */
    public function entityResourceClasses($names = null): array
    {
        if ($names === null) {
            return static::RESOURCE_RESOURCE_CLASSES;
        }
        $result = $this->resourceNames($names);
        return array_filter(array_map(fn ($v) => static::RESOURCE_RESOURCE_CLASSES[$v] ?? null, $result));
    }

    /**
     * Get the resource api name from any class, type or name.
     *
     * @param string $name
     * @return string|null The resource name if any.
     */
    public function resourceName($name): ?string
    {
        return static::RESOURCE_NAMES[$name] ?? null;
    }

    /**
     * Get the resource api names from any class, type or name or all of them.
     *
     * @param array|string|null $names
     * @return array The resource names if any, or all resource names as
     * associative array.
     */
    public function resourceNames($names = null): array
    {
        if (is_null($names)) {
            $result = array_unique(static::RESOURCE_NAMES);
            return array_combine($result, $result);
        }
        if (is_scalar($names)) {
            $names = [$names];
        }
        // Most of the times, only some names are searched, so reorder them by
        // default because it is very quick.
        $searchKeys = array_flip($names);
        $result = array_intersect_key(static::RESOURCE_NAMES, $searchKeys);
        return array_replace(array_intersect_key($searchKeys, $result), $result);
    }

    /**
     * Get the resource controller or class name from any class, type or name.
     *
     * @param string $name
     * @return string|null The resource controller or class name if any.
     */
    public function resourceType($name): ?string
    {
        return static::RESOURCE_TYPES[static::RESOURCE_NAMES[$name] ?? null] ?? null;
    }

    /**
     * Get the resource controller or class names from any class, type or name
     * or all of them.
     *
     * @param array|string|null $names
     * @return array The resource controller or class name if any, or all
     * controller or class names as associative array with resource api names
     * as key.
     */
    public function resourceTypes($names = null): array
    {
        if (is_null($names)) {
            return static::RESOURCE_TYPES;
        }
        $result = $this->resourceNames($names);
        return array_map(fn ($v) => static::RESOURCE_TYPES[$v], $result);
    }

    /**
     * Get the resource label from any common resource name.
     *
     * @return string|null The singular label if any, not translated.
     */
    public function resourceLabel($name): ?string
    {
        return static::RESOURCE_LABELS[static::RESOURCE_NAMES[$name] ?? null] ?? null;
    }

    /**
     * Get the plural resource label from any common resource name.
     *
     * @return string|null The plural label if any, not translated.
     */
    public function resourceLabelPlural($name): ?string
    {
        return static::RESOURCE_LABELS_PLURAL[static::RESOURCE_NAMES[$name] ?? null] ?? null;
    }

    /**
     * Get the resource label singular or plural from any common resource name.
     *
     * The plugin translatePlural() does not manage multiple placeholders.
     *
     * @return string|null The singular or plural label if any, not translated.
     */
    public function resourceLabelCount($name, int $count = 0): ?string
    {
        if ($count <= 1) {
            return static::RESOURCE_LABELS[static::RESOURCE_NAMES[$name] ?? null] ?? null;
        } else {
            return static::RESOURCE_LABELS_PLURAL[static::RESOURCE_NAMES[$name] ?? null] ?? null;
        }
    }

    /**
     * Get a data type name by name.
     *
     * @param string|null $name A name.
     * @return string|null The data type name matching the name.
     */
    public function dataTypeName(?string $dataType): ?string
    {
        if (!$dataType) {
            return null;
        }
        if (is_null(static::$dataTypesByNames)) {
            $this->initDataTypes();
        }
        $dataType = mb_strtolower($dataType);
        return static::$dataTypesByNames[$dataType] ?? null;
    }

    /**
     * Get data type names by names or all data types.
     *
     * @param array|int|string|null $dataTypes One or multiple data types.
     * @return string[] The matching data type names or all data types, by data
     * types.
     */
    public function dataTypeNames($dataTypes = null): array
    {
        if (is_null(static::$dataTypesByNames)) {
            $this->initDataTypes();
        }
        if (is_null($dataTypes)) {
            return static::$dataTypesByNames;
        }
        if (is_scalar($dataTypes)) {
            $dataTypes = [$dataTypes];
        }
        return array_intersect_key(static::$dataTypesByNames, array_flip($dataTypes));
    }

    /**
     * Get used data type names by names or all data types.
     *
     * @param array|int|string|null $dataTypes One or multiple data types.
     * @return string[] The matching used data type names or all used data
     * types, by data types.
     */
    public function dataTypeNamesUsed($dataTypes = null): array
    {
        if (is_null(static::$dataTypesByNamesUsed)) {
            $this->initDataTypesUsed();
        }
        if (is_null($dataTypes)) {
            return static::$dataTypesByNamesUsed;
        }
        if (is_scalar($dataTypes)) {
            $dataTypes = [$dataTypes];
        }
        return array_intersect_key(static::$dataTypesByNamesUsed, array_flip($dataTypes));
    }

    /**
     * Get data type labels by names or all data types.
     *
     * @param array|int|string|null $dataTypes One or multiple data types.
     * @return string[] The matching data type labels or all data types labels,
     * by data types.
     */
    public function dataTypeLabels($dataTypes = null): array
    {
        if (is_null(static::$dataTypeLabelsByNames)) {
            $this->initDataTypes();
        }
        if (is_null($dataTypes)) {
            return static::$dataTypeLabelsByNames;
        }
        if (is_scalar($dataTypes)) {
            $dataTypes = [$dataTypes];
        }
        return array_intersect_key(static::$dataTypeLabelsByNames, array_flip($dataTypes));
    }

    /**
     * Get the main data type ("literal", "resource", or "uri") of a name.
     *
     * @param string|null $name A name.
     * @return string|null The main data type name matching the name.
     */
    public function dataTypeMain(?string $dataType): ?string
    {
        if (!$dataType) {
            return null;
        }
        $name = mb_strtolower($dataType);
        if (isset(static::DATA_TYPES_MAIN[$dataType])) {
            return static::DATA_TYPES_MAIN[$dataType];
        }
        // Manage an exception in ValueSuggest: geonames features has no uri.
        if ($name === 'valuesuggestall:geonames:features') {
            return 'literal';
        }
        // Module ValueSuggest.
        if (substr($dataType, 0, 13) === 'valuesuggest:'
            || substr($dataType, 0, 16) === 'valuesuggestall:'
        ) {
            return 'uri';
        }
        if (substr($dataType, 0, 12) === 'customvocab:') {
            return $this->dataTypeMainCustomVocab($dataType);
        }
        return null;
    }

    /**
     * Get the main data types ("literal", "resource", or "uri") of names.
     *
     * @param string|null $name A name.
     * @return string|null The main data type names matching the names.
     */
    public function dataTypeMains($dataTypes = null): array
    {
        static $dataTypesMains;
        if (!is_array($dataTypesMains)) {
            $dataTypesMains = [];
            foreach ($this->dataTypeNames() as $dataType) {
                $dataTypesMains[$dataType] = $this->dataTypeMain($dataType);
            }
        }
        if ($dataTypes === null) {
            return $dataTypesMains;
        }
        if (is_scalar($dataTypes)) {
            $dataTypes = [$dataTypes];
        }
        // TODO Keep order.
        return array_intersect_key($dataTypesMains, array_flip($dataTypes));
    }

    /**
     * Get the main type of the custom vocab: "literal", "resource" or "uri".
     *
     * @todo Check for dynamic custom vocabs.
     */
    public function dataTypeMainCustomVocab(?string $dataType): ?string
    {
        if (!$dataType) {
            return null;
        }
        if (is_null(static::$dataTypesMainCustomVocabs)) {
            $this->initDataTypesMainCustomVocabs();
        }
        $dataType = mb_strtolower($dataType);
        return static::$dataTypesMainCustomVocabs[$dataType] ?? null;
    }

    /**
     * Get the custom vocabs main type ("literal", "resource" or "uri").
     *
     * @todo Check for dynamic custom vocabs.
     *
     * @param array|int|string|null $dataTypes One or multiple data types.
     * @return string[] The matching data type main types or all data types
     * main types, by data types.
     */
    public function dataTypeMainCustomVocabs($dataTypes = null): array
    {
        if (is_null(static::$dataTypesMainCustomVocabs)) {
            $this->initDataTypesMainCustomVocabs();
        }
        if (is_null($dataTypes)) {
            return static::$dataTypesMainCustomVocabs;
        }
        if (is_scalar($dataTypes)) {
            $dataTypes = [$dataTypes];
        }
        return array_intersect_key(static::$dataTypesMainCustomVocabs, array_flip($dataTypes));
    }

    /**
     * Get a property id by JSON-LD term or by numeric id.
     *
     * @param int|string|null $termOrId A id or a term.
     * @return int|null The property id matching term or id.
     */
    public function propertyId($termOrId): ?int
    {
        if (is_null(static::$propertyIdsByTermsAndIds)) {
            $this->initProperties();
        }
        return static::$propertyIdsByTermsAndIds[$termOrId] ?? null;
    }

    /**
     * Get property ids by JSON-LD terms or by numeric ids.
     *
     * @param array|int|string|null $termsOrIds One or multiple ids or terms.
     * @return int[] The property ids matching terms or ids, or all properties
     * by terms. When the input contains terms and ids matching the same
     * properties, they are all returned. Input order is kept.
     */
    public function propertyIds($termsOrIds = null): array
    {
        if (is_null(static::$propertyIdsByTermsAndIds)) {
            $this->initProperties();
        }
        if (!$termsOrIds) {
            return $termsOrIds === null
                ? static::$propertyIdsByTerms
                : [];
        }
        if (is_scalar($termsOrIds)) {
            $result = static::$propertyIdsByTermsAndIds[$termsOrIds] ?? null;
            return $result ? [$termsOrIds => $result] : [];
        }
        // Most of the times, only some terms are searched, so reorder them by
        // default because it is very quick.
        $searchKeys = array_flip($termsOrIds);
        $result = array_intersect_key(static::$propertyIdsByTermsAndIds, $searchKeys);
        return array_replace(array_intersect_key($searchKeys, $result), $result);
    }

    /**
     * Get used property ids by JSON-LD terms or by numeric ids.
     *
     * @param array|int|string|null $termsOrIds One or multiple ids or terms.
     * @return int[] The used property ids matching terms or ids, or all
     * used properties by terms. When the input contains terms and ids matching
     * the same properties, they are all returned. Input order is kept.
     */
    public function propertyIdsUsed($termsOrIds = null): array
    {
        if (is_null(static::$propertyIdsByTermsAndIdsUsed)) {
            $this->initPropertiesUsed();
        }
        if (!$termsOrIds) {
            return $termsOrIds === null
                ? array_diff_key(static::$propertyIdsByTermsAndIdsUsed, array_flip(static::$propertyIdsByTermsAndIdsUsed))
                : [];
        }
        if (is_scalar($termsOrIds)) {
            $result = static::$propertyIdsByTermsAndIdsUsed[$termsOrIds] ?? null;
            return $result ? [$termsOrIds => $result] : [];
        }
        // Most of the times, only some terms are searched, so reorder them by
        // default because it is very quick.
        $searchKeys = array_flip($termsOrIds);
        $result = array_intersect_key(static::$propertyIdsByTermsAndIdsUsed, $searchKeys);
        return array_replace(array_intersect_key($searchKeys, $result), $result);
    }

    /**
     * Get a property term by JSON-LD term or by numeric id.
     *
     * @param int|string|null $termOrId A id or a term.
     * @return string|null The property term matching term or id.
     */
    public function propertyTerm($termOrId): ?string
    {
        if (is_null(static::$propertyIdsByTermsAndIds)) {
            $this->initProperties();
        }
        if (!isset(static::$propertyIdsByTermsAndIds[$termOrId])) {
            return null;
        }
        return is_numeric($termOrId)
            ? array_search($termOrId, static::$propertyIdsByTerms)
            : $termOrId;
    }

    /**
     * Get property terms by JSON-LD terms or by numeric ids.
     *
     * @param array|int|string|null $termsOrIds One or multiple ids or terms.
     * @return string[] The property terms matching terms or ids, or all
     * properties by ids. When the input contains terms and ids matching the
     * same properties, they are all returned. Input order is kept.
     */
    public function propertyTerms($termsOrIds = null): array
    {
        if (is_null(static::$propertyIdsByTermsAndIds)) {
            $this->initProperties();
        }
        if (!$termsOrIds) {
            return $termsOrIds === null
                ? array_flip(static::$propertyIdsByTerms)
                : [];
        }
        if (is_null(static::$propertyTermsByTermsAndIds)) {
            $propertyTermsByIds = array_flip(static::$propertyIdsByTerms);
            static::$propertyTermsByTermsAndIds = array_combine($propertyTermsByIds, $propertyTermsByIds)
                + $propertyTermsByIds;
        }
        if (is_scalar($termsOrIds)) {
            $result = static::$propertyTermsByTermsAndIds[$termsOrIds] ?? null;
            return $result ? [$termsOrIds => $result] : [];
        }
        // Most of the times, only some terms are searched, so reorder them by
        // default because it is very quick.
        $searchKeys = array_flip($termsOrIds);
        $result = array_intersect_key(static::$propertyTermsByTermsAndIds, $searchKeys);
        return array_replace(array_intersect_key($searchKeys, $result), $result);
    }

    /**
     * Get a property label by JSON-LD term or by numeric id.
     *
     * @param int|string|null $termOrId A id or a term.
     * @return string|null The property label matching term or id. The label is
     * not translated.
     */
    public function propertyLabel($termOrId): ?string
    {
        if (is_null(static::$propertyIdsByTermsAndIds)) {
            $this->initProperties();
        }
        return static::$propertyLabelsByTermsAndIds[$termOrId] ?? null;
    }

    /**
     * Get property labels by JSON-LD terms or by numeric ids.
     *
     * @param array|int|string|null $termsOrIds One or multiple ids or terms.
     * @return string[] The property labels matching terms or ids, or all
     * properties labels by terms. When the input contains terms and ids
     * matching the same properties, they are all returned. Labels are not
     * translated.
     */
    public function propertyLabels($termsOrIds = null): array
    {
        if (is_null(static::$propertyLabelsByTerms)) {
            $this->initProperties();
        }
        if (!$termsOrIds) {
            return $termsOrIds === null
                ? static::$propertyLabelsByTerms
                : [];
        }
        if (is_scalar($termsOrIds)) {
            $result = static::$propertyLabelsByTermsAndIds[$termsOrIds] ?? null;
            return $result ? [$termsOrIds => $result] : [];
        }
        // TODO Keep original order.
        return array_intersect_key(static::$propertyLabelsByTermsAndIds, array_flip($termsOrIds));
    }

    /**
     * Get a resource class id by JSON-LD term or by numeric id.
     *
     * @param int|string|null $termOrId A id or a term.
     * @return int|null The resource class id matching term or id.
     */
    public function resourceClassId($termOrId): ?int
    {
        if (is_null(static::$resourceClassIdsByTermsAndIds)) {
            $this->initResourceClasses();
        }
        return static::$resourceClassIdsByTermsAndIds[$termOrId] ?? null;
    }

    /**
     * Get resource class ids by JSON-LD terms or by numeric ids.
     *
     * @param array|int|string|null $termsOrIds One or multiple ids or terms.
     * @return int[] The resource class ids matching terms or ids, or all
     * resource classes by terms. When the input contains terms and ids matching
     * the same resource classes, they are all returned. Input order is kept.
     */
    public function resourceClassIds($termsOrIds = null): array
    {
        if (is_null(static::$resourceClassIdsByTermsAndIds)) {
            $this->initResourceClasses();
        }
        if (!$termsOrIds) {
            return $termsOrIds === null
                ? static::$resourceClassIdsByTerms
                : [];
        }
        if (is_scalar($termsOrIds)) {
            $result = static::$resourceClassIdsByTermsAndIds[$termsOrIds] ?? null;
            return $result ? [$termsOrIds => $result] : [];
        }
        // Most of the times, only some terms are searched, so reorder them by
        // default because it is very quick.
        $searchKeys = array_flip($termsOrIds);
        $result = array_intersect_key(static::$resourceClassIdsByTermsAndIds, $searchKeys);
        return array_replace(array_intersect_key($searchKeys, $result), $result);
    }

    /**
     * Get used resource class ids by JSON-LD terms or by numeric ids.
     *
     * @param array|int|string|null $termsOrIds One or multiple ids or terms.
     * @return int[] The used resource class ids matching terms or ids, or all
     * used resource classes by terms. When the input contains terms and ids
     * matching the same resource classes, they are all returned. Input order is
     * kept.
     */
    public function resourceClassIdsUsed($termsOrIds = null): array
    {
        if (is_null(static::$resourceClassIdsByTermsAndIdsUsed)) {
            $this->initResourceClassesUsed();
        }
        if (!$termsOrIds) {
            return $termsOrIds === null
                ? array_diff_key(static::$resourceClassIdsByTermsAndIdsUsed, array_flip(static::$resourceClassIdsByTermsAndIdsUsed))
                : [];
        }
        if (is_scalar($termsOrIds)) {
            $result = static::$resourceClassIdsByTermsAndIdsUsed[$termsOrIds] ?? null;
            return $result ? [$termsOrIds => $result] : [];
        }
        // Most of the times, only some terms are searched, so reorder them by
        // default because it is very quick.
        $searchKeys = array_flip($termsOrIds);
        $result = array_intersect_key(static::$resourceClassIdsByTermsAndIdsUsed, $searchKeys);
        return array_replace(array_intersect_key($searchKeys, $result), $result);
    }

    /**
     * Get a resource class term by JSON-LD term or by numeric id.
     *
     * @param int|string|null $termOrId A id or a term.
     * @return string|null The resource class term matching term or id.
     */
    public function resourceClassTerm($termOrId): ?string
    {
        if (is_null(static::$resourceClassIdsByTermsAndIds)) {
            $this->initResourceClasses();
        }
        if (!isset(static::$resourceClassIdsByTermsAndIds[$termOrId])) {
            return null;
        }
        return is_numeric($termOrId)
            ? array_search($termOrId, static::$resourceClassIdsByTerms)
            : $termOrId;
    }

    /**
     * Get resource class terms by JSON-LD terms or by numeric ids.
     *
     * @param array|int|string|null $termsOrIds One or multiple ids or terms.
     * @return string[] The resource class terms matching terms or ids, or all
     * resource classes by ids. When the input contains terms and ids matching
     * the same resource classes, they are all returned. Input order is kept.
     */
    public function resourceClassTerms($termsOrIds = null): array
    {
        if (is_null(static::$resourceClassIdsByTermsAndIds)) {
            $this->initResourceClasses();
        }
        if (!$termsOrIds) {
            return $termsOrIds === null
                ? array_flip(static::$resourceClassIdsByTerms)
                : [];
        }
        if (is_null(static::$resourceClassTermsByTermsAndIds)) {
            $resourceClassTermsByIds = array_flip(static::$resourceClassIdsByTerms);
            static::$resourceClassTermsByTermsAndIds = array_combine($resourceClassTermsByIds, $resourceClassTermsByIds)
                + $resourceClassTermsByIds;
        }
        if (is_scalar($termsOrIds)) {
            $result = static::$resourceClassTermsByTermsAndIds[$termsOrIds] ?? null;
            return $result ? [$termsOrIds => $result] : [];
        }
        // Most of the times, only some terms are searched, so reorder them by
        // default because it is very quick.
        $searchKeys = array_flip($termsOrIds);
        $result = array_intersect_key(static::$resourceClassTermsByTermsAndIds, $searchKeys);
        return array_replace(array_intersect_key($searchKeys, $result), $result);
    }

    /**
     * Get a resource class label by JSON-LD term or by numeric id.
     *
     * @param int|string|null $termOrId A id or a term.
     * @return string|null The resource class label matching term or id. The
     * label is not translated.
     */
    public function resourceClassLabel($termOrId): ?string
    {
        if (is_null(static::$resourceClassIdsByTermsAndIds)) {
            $this->initResourceClasses();
        }
        return static::$resourceClassLabelsByTermsAndIds[$termOrId] ?? null;
    }

    /**
     * Get resource class labels by JSON-LD terms or by numeric ids.
     *
     * @param array|int|string|null $termsOrIds One or multiple ids or terms.
     * @return string[] The resource class labels matching terms or ids, or all
     * resource class labels by terms. When the input contains terms and ids
     * matching the same resource classes, they are all returned. Labels are not
     * translated.
     */
    public function resourceClassLabels($termsOrIds = null): array
    {
        if (is_null(static::$resourceClassLabelsByTerms)) {
            $this->initResourceClasses();
        }
        if (!$termsOrIds) {
            return $termsOrIds === null
                ? static::$resourceClassLabelsByTerms
                : [];
        }
        if (is_scalar($termsOrIds)) {
            $result = static::$resourceClassLabelsByTermsAndIds[$termsOrIds] ?? null;
            return $result ? [$termsOrIds => $result] : [];
        }
        // TODO Keep original order.
        return array_intersect_key(static::$resourceClassLabelsByTermsAndIds, array_flip($termsOrIds));
    }

    /**
     * Get a resource template id by label or by numeric id.
     *
     * @param int|string|null $labelOrId A id or a label.
     * @return int|null The resource template id matching label or id.
     */
    public function resourceTemplateId($labelOrId): ?int
    {
        if (is_null(static::$resourceTemplateIdsByLabelsAndIds)) {
            $this->initResourceTemplates();
        }
        return static::$resourceTemplateIdsByLabelsAndIds[$labelOrId] ?? null;
    }

    /**
     * Get resource template ids by labels or by numeric ids.
     *
     * @param array|int|string|null $labelsOrIds One or multiple ids or labels.
     * @return string[] The resource template ids matching labels or ids, or all
     * resource templates by labels. When the input contains labels and ids
     * matching the same templates, they are all returned. Input order is kept.
     */
    public function resourceTemplateIds($labelsOrIds = null): array
    {
        if (is_null(static::$resourceTemplateIdsByLabelsAndIds)) {
            $this->initResourceTemplates();
        }
        if (!$labelsOrIds) {
            return $labelsOrIds === null
                ? static::$resourceTemplateIdsByLabels
                : [];
        }
        if (is_scalar($labelsOrIds)) {
            $result = static::$resourceTemplateIdsByLabelsAndIds[$labelsOrIds] ?? null;
            return $result ? [$labelsOrIds => $result] : [];
        }
        // Most of the times, only some values are searched, so reorder them by
        // default because it is very quick.
        $searchKeys = array_flip($labelsOrIds);
        $result = array_intersect_key(static::$resourceTemplateIdsByLabelsAndIds, $searchKeys);
        return array_replace(array_intersect_key($searchKeys, $result), $result);
    }

    /**
     * Get used resource template ids by labels or by numeric ids.
     *
     * @param array|int|string|null $labelsOrIds One or multiple ids or labels.
     * @return string[] The used resource template ids matching labels or ids,
     * or all used resource templates by labels. When the input contains labels
     * and ids matching the same templates, they are all returned. Input order
     * is kept.
     */
    public function resourceTemplateIdsUsed($labelsOrIds = null): array
    {
        if (is_null(static::$resourceTemplateIdsByLabelsAndIdsUsed)) {
            $this->initResourceTemplatesUsed();
        }
        if (!$labelsOrIds) {
            return $labelsOrIds === null
                ? array_diff_key(static::$resourceTemplateIdsByLabelsAndIdsUsed, array_flip(static::$resourceTemplateIdsByLabelsAndIdsUsed))
                : [];
        }
        if (is_scalar($labelsOrIds)) {
            $result = static::$resourceTemplateIdsByLabelsAndIdsUsed[$labelsOrIds] ?? null;
            return $result ? [$labelsOrIds => $result] : [];
        }
        // Most of the times, only some values are searched, so reorder them by
        // default because it is very quick.
        $searchKeys = array_flip($labelsOrIds);
        $result = array_intersect_key(static::$resourceTemplateIdsByLabelsAndIdsUsed, $searchKeys);
        return array_replace(array_intersect_key($searchKeys, $result), $result);
    }

    /**
     * Get a resource template label by label or by numeric id.
     *
     * @param int|string|null $labelOrId A id or a label.
     * @return string|null The resource template label matching label or id.
     */
    public function resourceTemplateLabel($labelOrId): ?string
    {
        if (is_null(static::$resourceTemplateIdsByLabelsAndIds)) {
            $this->initResourceTemplates();
        }
        return static::$resourceTemplateIdsByLabelsAndIds[$labelOrId] ?? null;
    }

    /**
     * Get one or more resource template labels by labels or by numeric ids.
     *
     * @param array|int|string|null $labelsOrIds One or multiple ids or labels.
     * @return string[] The resource template labels matching labels or ids, or
     * all resource templates labels. When the input contains labels and ids
     * matching the same templates, they are all returned.
     */
    public function resourceTemplateLabels($labelsOrIds = null): array
    {
        if (is_null(static::$resourceTemplateIdsByLabelsAndIds)) {
            $this->initResourceTemplates();
        }
        if (!$labelsOrIds) {
            return $labelsOrIds === null
                ? array_flip(static::$resourceTemplateIdsByLabels)
                : [];
        }
        if (is_scalar($labelsOrIds)) {
            $result = static::$resourceTemplateIdsByLabelsAndIds[$labelsOrIds] ?? null;
            return $result ? [$labelsOrIds => $result] : [];
        }
        // TODO Keep original order.
        return array_intersect_key(static::$resourceTemplateLabelsByLabelsAndIds, array_flip($labelsOrIds));
    }

    /**
     * Get a resource template class by label or id.
     *
     * @param int|string|null $labelOrId A id or a label.
     * @return string|null The resource class matching label or id.
     */
    public function resourceTemplateClassId($labelOrId): ?int
    {
        $templateId = $this->resourceTemplateId($labelOrId);
        if (!$templateId) {
            return null;
        }
        $classIds = $this->resourceTemplateClassIds();
        return $classIds[$templateId] ?? null;
    }

    /**
     * Get one or more resource template class ids by labels or by numeric ids.
     *
     * @param array|int|string|null $labelsOrIds One or multiple ids or labels.
     * @return int[] The resource template class ids matching labels or ids, or
     * all resource template class ids. When the input contains labels and ids
     * matching the same templates, they are mixed.
     * @todo Return list by labels and ids together like other (but this method is rarely used).
     */
    public function resourceTemplateClassIds($labelsOrIds = null): array
    {
        if (is_null(static::resourceTemplateClassesByIds)) {
            $this->initResourceTemplateClasses();
        }
        if (!$labelsOrIds) {
            return $labelsOrIds === null
                ? static::resourceTemplateClassesByIds
                : [];
        }
        if (is_scalar($labelsOrIds)) {
            $labelsOrIds = [$labelsOrIds];
        }
        $templateIds = $this->resourceTemplateIds($labelsOrIds);
        if (!$templateIds) {
            return [];
        }
        // TODO Keep original order.
        return array_intersect_key(static::resourceTemplateClassesByIds, array_flip($labelsOrIds));
    }

    /**
     * Get a vocabulary id by uri, prefix or by numeric id.
     *
     * @param int|string|null $prefixOrUriOrId A id, uri or prefix.
     * @return int|null The vocabulary id matching uri, prefix or id.
     */
    public function vocabularyId($prefixOrUriOrId): ?int
    {
        if (is_null(static::$vocabularyIdsByPrefixesAndUrisAndIds)) {
            $this->initVocabularies();
        }
        return static::$vocabularyIdsByPrefixesAndUrisAndIds[$prefixOrUriOrId] ?? null;
    }

    /**
     * Get vocabulary ids by uris, prefix or by numeric ids.
     *
     * @param array|int|string|null $prefixesOrUrisOrIds One or multiple ids,
     * uris or prefixes.
     * @return int[] The vocabulary ids matching uris, prefixes or ids, or all
     * vocabularies by prefixes. When the input contains uris, prefixes and ids
     * matching the same vocabularies, they are all returned.
     */
    public function vocabularyIds($prefixesOrUrisOrIds = null): array
    {
        if (is_null(static::$vocabularyIdsByPrefixesAndUrisAndIds)) {
            $this->initVocabularies();
        }
        if (!$prefixesOrUrisOrIds) {
            return $prefixesOrUrisOrIds === null
                ? static::$vocabularyIdsByPrefixes
                : [];
        }
        if (is_scalar($prefixesOrUrisOrIds)) {
            $prefixesOrUrisOrIds = [$prefixesOrUrisOrIds];
        }
        return array_intersect_key(static::$vocabularyIdsByPrefixesAndUrisAndIds, array_flip($prefixesOrUrisOrIds));
    }

    /**
     * Get a vocabulary prefix by uri, prefix or by numeric id.
     *
     * @param int|string|null $prefixOrUriOrId A id, uri or prefix.
     * @return int|null The vocabulary prefix matching uri, prefix or id.
     */
    public function vocabularyPrefix($prefixOrUriOrId): ?string
    {
        if (is_null(static::$vocabularyIdsByPrefixesAndUrisAndIds)) {
            $this->initVocabularies();
        }
        if (!isset(static::$vocabularyIdsByPrefixesAndUrisAndIds[$prefixOrUriOrId])) {
            return null;
        }
        if (is_numeric($prefixOrUriOrId)) {
            return array_search($prefixOrUriOrId, static::$vocabularyIdsByPrefixes);
        }
        $id = static::$vocabularyIdsByPrefixesAndUrisAndIds[$prefixOrUriOrId];
        return array_search($id, static::$vocabularyIdsByPrefixes);
    }

    /**
     * Get vocabulary prefixes by uris, prefixes or numeric ids.
     *
     * @param array|int|string|null $prefixesOrUrisOrIds One or multiple ids,
     * uris or prefixes.
     * @return string[] The vocabulary prefixes matching uris, prefixes or ids,
     * or all vocabularies prefixes by ids. When the input contains uris,
     * prefixes and ids matching the same vocabularies, they are all returned.
     */
    public function vocabularyPrefixes($prefixesOrUrisOrIds = null): array
    {
        if (is_null(static::$vocabularyIdsByPrefixesAndUrisAndIds)) {
            $this->initVocabularies();
        }
        if (!$prefixesOrUrisOrIds) {
            return $prefixesOrUrisOrIds === null
                ? array_flip(static::$vocabularyIdsByPrefixes)
                : [];
        }
        if (is_scalar($prefixesOrUrisOrIds)) {
            $prefixesOrUrisOrIds = [$prefixesOrUrisOrIds];
        }
        // TODO Keep original order.
        return array_intersect_key(static::$vocabularyPrefixesByPrefixesAndUrisAndIds, array_flip($prefixesOrUrisOrIds));
    }

    /**
     * Get a vocabulary uri by uri, prefix or numeric id.
     *
     * @param int|string|null $prefixOrUriOrId A id, uri or prefix.
     * @return string|null The vocabulary uri matching uri, prefix or id.
     */
    public function vocabularyUri($prefixOrUriOrId): ?string
    {
        if (is_null(static::$vocabularyIdsByPrefixesAndUrisAndIds)) {
            $this->initVocabularies();
        }
        if (!isset(static::$vocabularyIdsByPrefixesAndUrisAndIds[$prefixOrUriOrId])) {
            return null;
        }
        if (is_numeric($prefixOrUriOrId)) {
            return array_search($prefixOrUriOrId, static::$vocabularyIdsByUris);
        }
        $id = static::$vocabularyIdsByPrefixesAndUrisAndIds[$prefixOrUriOrId];
        return array_search($id, static::$vocabularyIdsByUris);
    }

    /**
     * Get vocabulary uris by uris, prefixes or numeric ids.
     *
     * @param array|int|string|null $prefixesOrUrisOrIds One or multiple ids,
     * uris or prefixes.
     * @return string[] The vocabulary uris matching ids, uris or prefixes, or
     * all vocabulary uris by prefixes. When the input contains uris, prefixes
     * and ids matching the same vocabularies, they are all returned.
     */
    public function vocabularyUrisByPrefixes($prefixesOrUrisOrIds = null): array
    {
        if (is_null(static::$vocabularyIdsByPrefixesAndUrisAndIds)) {
            $this->initVocabularies();
        }
        if (!$prefixesOrUrisOrIds) {
            return $prefixesOrUrisOrIds === null
                ? array_combine(array_keys(static::$vocabularyIdsByPrefixes), array_keys(static::$vocabularyIdsByUris))
                : [];
        }
        if (is_scalar($prefixesOrUrisOrIds)) {
            $prefixesOrUrisOrIds = [$prefixesOrUrisOrIds];
        }
        // TODO Keep original order.
        return array_intersect_key(static::$vocabularyUrisByPrefixesAndUrisAndIds, array_flip($prefixesOrUrisOrIds));
    }

    /**
     * Get a vocabulary label by uris, prefixes or numeric ids.
     *
     * @param array|int|string|null $prefixesOrUrisOrIds One or multiple ids,
     * uris or prefixes.
     * @return string|null The vocabulary label matching uri, prefix or id.
     */
    public function vocabularyLabel($prefixOrUriOrId): ?string
    {
        if (is_null(static::$vocabularyLabelsByPrefixesAndUrisAndIds)) {
            $this->initVocabularies();
        }
        return static::$vocabularyLabelsByPrefixesAndUrisAndIds[$prefixOrUriOrId] ?? null;
    }

    /**
     * Get vocabulary labels by uris, prefixes or numeric ids.
     *
     * @param array|int|string|null $prefixesOrUrisOrIds One or multiple ids,
     * uris or prefixes.
     * @return string[] The vocabulary labels matching ids, uris or prefixes, or
     * all vocabulary labels by prefixes. When the input contains uris, prefixes
     * and ids matching the same vocabularies, they are all returned.
     */
    public function vocabularyLabels($prefixesOrUrisOrIds = null): array
    {
        if (is_null(static::$vocabularyLabelsByPrefixesAndUrisAndIds)) {
            $this->initVocabularies();
        }
        if (!$prefixesOrUrisOrIds) {
            return $prefixesOrUrisOrIds === null
                ? array_intersect_key(static::$vocabularyLabelsByPrefixesAndUrisAndIds, static::$vocabularyIdsByPrefixes)
                : [];
        }
        if (is_scalar($prefixesOrUrisOrIds)) {
            $prefixesOrUrisOrIds = [$prefixesOrUrisOrIds];
        }
        return array_intersect_key(static::$vocabularyLabelsByPrefixesAndUrisAndIds, array_flip($prefixesOrUrisOrIds));
    }

    protected function initDataTypes(): void
    {
        static::$dataTypesByNames = $this->dataTypeManager->getRegisteredNames();
        static::$dataTypesByNames = array_combine(static::$dataTypesByNames, static::$dataTypesByNames);
        foreach (static::$dataTypesByNames as $dataTypeName) {
            $dataType = $this->dataTypeManager->get($dataTypeName);
            static::$dataTypeLabelsByNames[$dataTypeName] = $dataType->getLabel();
        }
    }

    protected function initDataTypesUsed(): void
    {
        // Most of the time, we don't need used data types and all data types at
        // the same time, so fetching them is done separately of initDataTypes().
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select(
                '`value`.`type` AS name',
                '`value`.`type` AS name2'
            )
            ->from('`value`', 'value')
            ->groupBy('`value`.`type`')
            ->orderBy('`value`.`type`', 'asc')
        ;
        static::$dataTypesByNamesUsed = $this->connection->executeQuery($qb)->fetchAllKeyValue();
    }

    protected function initDataTypesMainCustomVocabs(): void
    {
        $hasCustomVocab = class_exists('CustomVocab\Module');
        if ($hasCustomVocab) {
            /*
            $sql = <<<'SQL'
            SELECT
                GROUP_CONCAT(DISTINCT CASE WHEN `terms` != "" THEN `id` ELSE NULL END ORDER BY `id` ASC SEPARATOR " ") AS 'literal',
                GROUP_CONCAT(DISTINCT CASE WHEN `item_set_id` IS NOT NULL THEN `id` ELSE NULL END ORDER BY `id` ASC SEPARATOR " ") AS 'resource',
                GROUP_CONCAT(DISTINCT CASE WHEN `uris` != "" THEN `id` ELSE NULL END ORDER BY `id` ASC SEPARATOR " ") AS 'uri'
            FROM `custom_vocab`;
            SQL;
            $customVocabsByType = $site->get('Omeka\Connection')->executeQuery($sql)->fetchAssociative() ?: ['literal' => '', 'resource' => '', 'uri' => ''];
             */
            $sql = <<<'SQL'
SELECT
    CONCAT('customvocab:', `id`) AS "customvocab",
    CASE
        WHEN `uris` != "" THEN "uri"
        WHEN `item_set_id` IS NOT NULL THEN "resource"
        ELSE "literal"
    END AS "type"
FROM `custom_vocab`
ORDER BY `id` ASC;
SQL;
            static::$dataTypesMainCustomVocabs = $this->connection->executeQuery($sql)->fetchAllKeyValue() ?: [];
        } else {
            static::$dataTypesMainCustomVocabs = [];
        }
    }

    protected function initProperties(): void
    {
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select(
                'CONCAT(`vocabulary`.`prefix`, ":", `property`.`local_name`) AS term',
                '`property`.`id` AS id',
                '`property`.`label` AS label'
            )
            ->from('`property`', 'property')
            ->innerJoin('property', 'vocabulary', 'vocabulary', '`property`.`vocabulary_id` = `vocabulary`.`id`')
            ->groupBy('`property`.`id`')
            ->orderBy('`vocabulary`.`id`', 'asc')
            ->addOrderBy('`property`.`id`', 'asc')
        ;
        $result = $this->connection->executeQuery($qb)->fetchAllAssociative();

        static::$propertyIdsByTerms = array_map('intval', array_column($result, 'id', 'term'));
        static::$propertyIdsByTermsAndIds = static::$propertyIdsByTerms
            + array_map('intval', array_column($result, 'id', 'id'));
        static::$propertyLabelsByTerms = array_column($result, 'label', 'term');
        static::$propertyLabelsByTermsAndIds = static::$propertyLabelsByTerms
            + array_column($result, 'label', 'id');
    }

    protected function initPropertiesUsed(): void
    {
        // Most of the time, we don't need used properties and all properties at
        // the same time, so fetching them is done separately of initProperties().
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select(
                'CONCAT(`vocabulary`.`prefix`, ":", `property`.`local_name`) AS term',
                '`property`.`id` AS id'
            )
            ->from('`property`', 'property')
            ->innerJoin('property', 'vocabulary', 'vocabulary', '`property`.`vocabulary_id` = `vocabulary`.`id`')
            ->innerJoin('property', 'value', 'value', '`property`.`id` = `value`.`property_id`')
            ->groupBy('`property`.`id`')
            ->orderBy('`vocabulary`.`id`', 'asc')
            ->addOrderBy('`property`.`id`', 'asc')
        ;
        $result = $this->connection->executeQuery($qb)->fetchAllKeyValue();
        $propertyIdsByTerms = array_map('intval', $result);
        $propertyIdsByIds = array_combine($propertyIdsByTerms, $propertyIdsByTerms);
        static::$propertyIdsByTermsAndIdsUsed = $propertyIdsByTerms + $propertyIdsByIds;
    }

    protected function initResourceClasses(): void
    {
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select(
                'CONCAT(`vocabulary`.`prefix`, ":", `resource_class`.`local_name`) AS term',
                '`resource_class`.`id` AS id',
                '`resource_class`.`label` AS label'
            )
            ->from('`resource_class`', 'resource_class')
            ->innerJoin('resource_class', 'vocabulary', 'vocabulary', '`resource_class`.`vocabulary_id` = `vocabulary`.`id`')
            ->groupBy('`resource_class`.`id`')
            ->orderBy('`vocabulary`.`id`', 'asc')
            ->addOrderBy('`resource_class`.`id`', 'asc')
        ;
        $result = $this->connection->executeQuery($qb)->fetchAllAssociative();
        static::$resourceClassIdsByTerms = array_map('intval', array_column($result, 'id', 'term'));
        static::$resourceClassIdsByTermsAndIds = static::$resourceClassIdsByTerms
            + array_column($result, 'id', 'id');
        static::$resourceClassLabelsByTerms = array_column($result, 'label', 'term');
        static::$resourceClassLabelsByTermsAndIds = static::$resourceClassLabelsByTerms
            + array_column($result, 'label', 'id');
    }

    protected function initResourceClassesUsed(): void
    {
        // Most of the time, we don't need used classes and all classes at the
        // same time, so fetching them is done separately of initResourceClasses().
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select(
                'CONCAT(`vocabulary`.`prefix`, ":", `resource_class`.`local_name`) AS term',
                '`resource_class`.`id` AS id'
            )
            ->from('`resource_class`', 'resource_class')
            ->innerJoin('resource_class', 'vocabulary', 'vocabulary', '`resource_class`.`vocabulary_id` = `vocabulary`.`id`')
            ->innerJoin('resource_class', 'resource', 'resource', '`resource_class`.`id` = `resource`.`resource_class_id`')
            ->groupBy('`resource_class`.`id`')
            ->orderBy('`vocabulary`.`id`', 'asc')
            ->addOrderBy('`resource_class`.`id`', 'asc')
        ;
        $result = $this->connection->executeQuery($qb)->fetchAllKeyValue();
        $resourceClassIdsByTerms = array_map('intval', $result);
        $resourceClassIdsByIds = array_combine($resourceClassIdsByTerms, $resourceClassIdsByTerms);
        static::$resourceClassIdsByTermsAndIdsUsed = $resourceClassIdsByTerms + $resourceClassIdsByIds;
    }

    protected function initResourceTemplates(): void
    {
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select(
                '`resource_template`.`label` AS label',
                '`resource_template`.`id` AS id'
            )
            ->from('resource_template', 'resource_template')
            ->groupBy('`resource_template`.`id`')
            ->orderBy('`resource_template`.`label`', 'asc')
        ;
        $result = $this->connection->executeQuery($qb)->fetchAllKeyValue();
        static::$resourceTemplateIdsByLabels = array_map('intval', $result);
        static::$resourceTemplateIdsByLabelsAndIds = static::$resourceTemplateIdsByLabels
            + array_column($result, 'id', 'id');
        static::$resourceTemplateLabelsByLabelsAndIds = array_combine(array_keys(static::$resourceTemplateIdsByLabels), array_keys(static::$resourceTemplateIdsByLabels))
            + array_flip(static::$resourceTemplateIdsByLabels);
    }

    protected function initResourceTemplatesUsed(): void
    {
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select(
                '`resource_template`.`label` AS label',
                '`resource_template`.`id` AS id'
            )
            ->from('resource_template', 'resource_template')
            ->innerJoin('resource_template', 'resource', 'resource', '`resource_template`.`id` = `resource`.`resource_template_id`')
            ->groupBy('`resource_template`.`id`')
            ->orderBy('`resource_template`.`label`', 'asc')
        ;
        $result = $this->connection->executeQuery($qb)->fetchAllKeyValue();
        $resourceTemplateIdsByLabels = array_map('intval', $result);
        $resourceTemplateIdsByIds = array_combine($resourceTemplateIdsByLabels, $resourceTemplateIdsByLabels);
        static::$resourceTemplateIdsByLabelsAndIdsUsed = $resourceTemplateIdsByLabels + $resourceTemplateIdsByIds;
    }

    protected function initResourceTemplateClasses(): void
    {
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select(
                'resource_template.id AS id',
                'resource_template.resource_class_id AS class_id'
            )
            ->from('resource_template', 'resource_template')
            ->orderBy('resource_template.id', 'asc')
        ;
        $result = $this->connection->executeQuery($qb)->fetchAllKeyValue();
        static::$resourceTemplateClassesByIds = array_map(fn ($v) => $v ? null : (int) $v, $result);
    }

    protected function initVocabularies(): void
    {
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select(
                '`vocabulary`.`prefix` AS prefix',
                '`vocabulary`.`namespace_uri` AS uri',
                '`vocabulary`.`label` AS label',
                '`vocabulary`.`id` AS id'
            )
            ->from('`vocabulary`', 'vocabulary')
            ->groupBy('`vocabulary`.`id`')
            ->orderBy('`vocabulary`.`id`', 'asc')
        ;
        $result = $this->connection->executeQuery($qb)->fetchAllAssociative();

        static::$vocabularyIdsByPrefixes = array_map('intval', array_column($result, 'id', 'prefix'));
        static::$vocabularyIdsByUris = array_map('intval', array_column($result, 'id', 'uri'));
        static::$vocabularyIdsByPrefixesAndUrisAndIds = static::$vocabularyIdsByPrefixes
            + static::$vocabularyIdsByUris
            + array_combine(static::$vocabularyIdsByPrefixes, static::$vocabularyIdsByPrefixes);
        static::$vocabularyLabelsByPrefixesAndUrisAndIds = array_column($result, 'label', 'prefix')
            + array_column($result, 'label', 'uri')
            + array_column($result, 'label', 'id');
        static::$vocabularyPrefixesByPrefixesAndUrisAndIds = array_column($result, 'prefix', 'prefix')
            + array_column($result, 'prefix', 'uri')
            + array_column($result, 'prefix', 'id');
        static::$vocabularyUrisByPrefixesAndUrisAndIds = array_column($result, 'uri', 'prefix')
            + array_column($result, 'uri', 'uri')
            + array_column($result, 'uri', 'id');
    }
}
