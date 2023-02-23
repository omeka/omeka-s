<?php
namespace Omeka\Api\Adapter;

use DateTime;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Representation\ValueRepresentation;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Entity\Resource;
use Omeka\Stdlib\ErrorStore;
use Omeka\Stdlib\Message;

abstract class AbstractResourceEntityAdapter extends AbstractEntityAdapter implements FulltextSearchableInterface
{
    public function buildQuery(QueryBuilder $qb, array $query)
    {
        $this->buildPropertyQuery($qb, $query);

        if (isset($query['search'])) {
            $this->buildPropertyQuery($qb, ['property' => [[
                'property' => null,
                'type' => 'in',
                'text' => $query['search'],
            ]]]);
        }

        if (isset($query['owner_id']) && is_numeric($query['owner_id'])) {
            $userAlias = $this->createAlias();
            $qb->innerJoin(
                'omeka_root.owner',
                $userAlias
            );
            $qb->andWhere($qb->expr()->eq(
                "$userAlias.id",
                $this->createNamedParameter($qb, $query['owner_id']))
            );
        }

        if (isset($query['resource_class_label'])) {
            $resourceClassAlias = $this->createAlias();
            $qb->innerJoin(
                'omeka_root.resourceClass',
                $resourceClassAlias
            );
            $qb->andWhere($qb->expr()->eq(
                "$resourceClassAlias.label",
                $this->createNamedParameter($qb, $query['resource_class_label']))
            );
        }

        if (isset($query['resource_class_id'])) {
            $classes = $query['resource_class_id'];
            if (!is_array($classes)) {
                $classes = [$classes];
            }
            $classes = array_filter($classes, 'is_numeric');
            if ($classes) {
                $qb->andWhere($qb->expr()->in(
                    'omeka_root.resourceClass',
                    $this->createNamedParameter($qb, $classes)
                ));
            }
        }

        if (isset($query['resource_template_label'])) {
            $resourceTemplateAlias = $this->createAlias();
            $qb->innerJoin(
                'omeka_root.resourceTemplate',
                $resourceTemplateAlias
            );
            $qb->andWhere($qb->expr()->eq(
                "$resourceTemplateAlias.label",
                $this->createNamedParameter($qb, $query['resource_template_label']))
            );
        }

        if (isset($query['resource_template_id'])) {
            $templates = $query['resource_template_id'];
            if (!is_array($templates)) {
                $templates = [$templates];
            }
            $templates = array_filter($templates, 'is_numeric');
            if ($templates) {
                $qb->andWhere($qb->expr()->in(
                    'omeka_root.resourceTemplate',
                    $this->createNamedParameter($qb, $templates)
                ));
            }
        }

        if (isset($query['is_public']) && (is_numeric($query['is_public']) || is_bool($query['is_public']))) {
            $qb->andWhere($qb->expr()->eq(
                'omeka_root.isPublic',
                $this->createNamedParameter($qb, (bool) $query['is_public'])
            ));
        }

        $dateSearches = [
            'modified_before' => ['lt', 'modified'],
            'modified_after' => ['gt', 'modified'],
            'created_before' => ['lt', 'created'],
            'created_after' => ['gt', 'created'],
        ];
        $dateGranularities = [
            DateTime::ISO8601,
            '!Y-m-d\TH:i:s',
            '!Y-m-d\TH:i',
            '!Y-m-d\TH',
            '!Y-m-d',
            '!Y-m',
            '!Y',
        ];
        foreach ($dateSearches as $dateSearchKey => $dateSearch) {
            if (isset($query[$dateSearchKey])) {
                foreach ($dateGranularities as $dateGranularity) {
                    $date = DateTime::createFromFormat($dateGranularity, $query[$dateSearchKey]);
                    if (false !== $date) {
                        break;
                    }
                }
                $qb->andWhere($qb->expr()->{$dateSearch[0]}(
                    sprintf('omeka_root.%s', $dateSearch[1]),
                    // If the date is invalid, pass null to ensure no results.
                    $this->createNamedParameter($qb, $date ?: null)
                ));
            }
        }
    }

    public function sortQuery(QueryBuilder $qb, array $query)
    {
        if (is_string($query['sort_by'])) {
            $property = $this->getPropertyByTerm($query['sort_by']);
            if ($property) {
                $valuesAlias = $this->createAlias();
                $qb->leftJoin(
                    "omeka_root.values", $valuesAlias,
                    'WITH', $qb->expr()->eq("$valuesAlias.property", $property->getId())
                );
                $qb->addOrderBy(
                    "GROUP_CONCAT($valuesAlias.value ORDER BY $valuesAlias.id)",
                    $query['sort_order']
                );
            } elseif ('resource_class_label' == $query['sort_by']) {
                $resourceClassAlias = $this->createAlias();
                $qb->leftJoin("omeka_root.resourceClass", $resourceClassAlias)
                    ->addOrderBy("$resourceClassAlias.label", $query['sort_order']);
            } elseif ('resource_template_label' == $query['sort_by']) {
                $resourceTemplateAlias = $this->createAlias();
                $qb->leftJoin("omeka_root.resourceTemplate", $resourceTemplateAlias)
                    ->addOrderBy("$resourceTemplateAlias.label", $query['sort_order']);
            } elseif ('owner_name' == $query['sort_by']) {
                $ownerAlias = $this->createAlias();
                $qb->leftJoin("omeka_root.owner", $ownerAlias)
                    ->addOrderBy("$ownerAlias.name", $query['sort_order']);
            } else {
                parent::sortQuery($qb, $query);
            }
        }
    }

    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        $data = $request->getContent();

        if ($this->shouldHydrate($request, 'o:is_public')) {
            $entity->setIsPublic($request->getValue('o:is_public', true));
        }

        // Hydrate this resource's values.
        $valueHydrator = (new ValueHydrator)->hydrate($request, $entity, $this);

        // o:owner
        $this->hydrateOwner($request, $entity);

        // o:resource_class
        $this->hydrateResourceClass($request, $entity);

        // o:resource_template
        $this->hydrateResourceTemplate($request, $entity);

        // o:title
        (new ResourceTitleHydrator)->hydrate($entity, $this->getPropertyByTerm('dcterms:title'));

        // o:thumbnail
        $this->hydrateThumbnail($request, $entity);

        $this->updateTimestamps($request, $entity);
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        $resourceTemplate = $entity->getResourceTemplate();
        if ($resourceTemplate) {
            // Confirm that a value exists for each required property.
            $criteria = Criteria::create()->where(Criteria::expr()->eq('isRequired', true));
            $requiredProps = $resourceTemplate->getResourceTemplateProperties()->matching($criteria);
            foreach ($requiredProps as $requiredProp) {
                $propExists = $entity->getValues()->exists(
                    function ($key, $element) use ($requiredProp) {
                        return $requiredProp->getProperty()->getId()
                            === $element->getProperty()->getId();
                    }
                );
                if (!$propExists) {
                    $errorStore->addError('o:resource_template_property', new Message(
                        'The "%1$s" resource template requires a "%2$s" value', // @translate
                        $resourceTemplate->getLabel(),
                        $requiredProp->getAlternateLabel()
                            ? $requiredProp->getAlternateLabel()
                            : $requiredProp->getProperty()->getLabel()
                    ));
                }
            }
        }
    }

    /**
     * Build query on value.
     *
     * Query format:
     *
     *   - property[{index}][joiner]: "and" OR "or" joiner with previous query
     *   - property[{index}][property]: property ID
     *   - property[{index}][text]: search text
     *   - property[{index}][type]: search type
     *     - eq: is exactly
     *     - neq: is not exactly
     *     - in: contains
     *     - nin: does not contain
     *     - ex: has any value
     *     - nex: has no value
     *     - sw: starts with
     *     - nsw: does not start with
     *     - ew: ends with
     *     - new: does not end with
     *     - res: has resource
     *     - nres: has no resource
     *
     * @param QueryBuilder $qb
     * @param array $query
     */
    protected function buildPropertyQuery(QueryBuilder $qb, array $query)
    {
        if (!isset($query['property']) || !is_array($query['property'])) {
            return;
        }
        $valuesJoin = 'omeka_root.values';
        $where = '';

        $escapeSqlLike = function ($string) {
            return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], (string) $string);
        };

        foreach ($query['property'] as $queryRow) {
            if (!(is_array($queryRow)
                && array_key_exists('property', $queryRow)
                && array_key_exists('type', $queryRow)
            )) {
                continue;
            }
            $propertyId = $queryRow['property'];
            $queryType = $queryRow['type'];
            $joiner = $queryRow['joiner'] ?? null;
            $value = isset($queryRow['text']) ? trim($queryRow['text']) : null;

            if (!$value && $queryType !== 'nex' && $queryType !== 'ex') {
                continue;
            }

            $valuesAlias = $this->createAlias();
            $positive = true;

            switch ($queryType) {
                case 'neq':
                    $positive = false;
                    // No break.
                case 'eq':
                    $param = $this->createNamedParameter($qb, $value);
                    $subqueryAlias = $this->createAlias();
                    $subquery = $this->getEntityManager()
                        ->createQueryBuilder()
                        ->select("$subqueryAlias.id")
                        ->from('Omeka\Entity\Resource', $subqueryAlias)
                        ->where($qb->expr()->eq("$subqueryAlias.title", $param));
                    $predicateExpr = $qb->expr()->orX(
                        $qb->expr()->in("$valuesAlias.valueResource", $subquery->getDQL()),
                        $qb->expr()->eq("$valuesAlias.value", $param),
                        $qb->expr()->eq("$valuesAlias.uri", $param)
                    );
                    break;

                case 'nin':
                    $positive = false;
                    // No break.
                case 'in':
                    $param = $this->createNamedParameter($qb, '%' . $escapeSqlLike($value) . '%');
                    $subqueryAlias = $this->createAlias();
                    $subquery = $this->getEntityManager()
                        ->createQueryBuilder()
                        ->select("$subqueryAlias.id")
                        ->from('Omeka\Entity\Resource', $subqueryAlias)
                        ->where($qb->expr()->like("$subqueryAlias.title", $param));
                    $predicateExpr = $qb->expr()->orX(
                        $qb->expr()->in("$valuesAlias.valueResource", $subquery->getDQL()),
                        $qb->expr()->like("$valuesAlias.value", $param),
                        $qb->expr()->like("$valuesAlias.uri", $param)
                    );
                    break;

                case 'nsw':
                    $positive = false;
                    // No break.
                case 'sw':
                    $param = $this->createNamedParameter($qb, $escapeSqlLike($value) . '%');
                    $subqueryAlias = $this->createAlias();
                    $subquery = $this->getEntityManager()
                        ->createQueryBuilder()
                        ->select("$subqueryAlias.id")
                        ->from('Omeka\Entity\Resource', $subqueryAlias)
                        ->where($qb->expr()->like("$subqueryAlias.title", $param));
                    $predicateExpr = $qb->expr()->orX(
                        $qb->expr()->in("$valuesAlias.valueResource", $subquery->getDQL()),
                        $qb->expr()->like("$valuesAlias.value", $param),
                        $qb->expr()->like("$valuesAlias.uri", $param)
                    );
                    break;

                case 'new':
                    $positive = false;
                    // No break.
                case 'ew':
                    $param = $this->createNamedParameter($qb, '%' . $escapeSqlLike($value));
                    $subqueryAlias = $this->createAlias();
                    $subquery = $this->getEntityManager()
                        ->createQueryBuilder()
                        ->select("$subqueryAlias.id")
                        ->from('Omeka\Entity\Resource', $subqueryAlias)
                        ->where($qb->expr()->like("$subqueryAlias.title", $param));
                    $predicateExpr = $qb->expr()->orX(
                        $qb->expr()->in("$valuesAlias.valueResource", $subquery->getDQL()),
                        $qb->expr()->like("$valuesAlias.value", $param),
                        $qb->expr()->like("$valuesAlias.uri", $param)
                    );
                    break;

                case 'nres':
                    $positive = false;
                    // No break.
                case 'res':
                    $predicateExpr = $qb->expr()->eq(
                        "$valuesAlias.valueResource",
                        $this->createNamedParameter($qb, $value)
                    );
                    break;

                case 'nex':
                    $positive = false;
                    // No break.
                case 'ex':
                    $predicateExpr = $qb->expr()->isNotNull("$valuesAlias.id");
                    break;

                default:
                    continue 2;
            }

            $joinConditions = [];
            // Narrow to specific property, if one is selected
            if ($propertyId) {
                if (is_numeric($propertyId)) {
                    $propertyId = (int) $propertyId;
                } else {
                    $property = $this->getPropertyByTerm($propertyId);
                    if ($property) {
                        $propertyId = $property->getId();
                    } else {
                        $propertyId = 0;
                    }
                }
                $joinConditions[] = $qb->expr()->eq("$valuesAlias.property", (int) $propertyId);
            }

            if ($positive) {
                $whereClause = '(' . $predicateExpr . ')';
            } else {
                $joinConditions[] = $predicateExpr;
                $whereClause = $qb->expr()->isNull("$valuesAlias.id");
            }

            if ($joinConditions) {
                $qb->leftJoin($valuesJoin, $valuesAlias, 'WITH', $qb->expr()->andX(...$joinConditions));
            } else {
                $qb->leftJoin($valuesJoin, $valuesAlias);
            }

            if ($where == '') {
                $where = $whereClause;
            } elseif ($joiner == 'or') {
                $where .= " OR $whereClause";
            } else {
                $where .= " AND $whereClause";
            }
        }

        if ($where) {
            $qb->andWhere($where);
        }
    }

    /**
     * Get a property entity by JSON-LD term.
     *
     * @param string $term
     * @return EntityInterface
     */
    public function getPropertyByTerm($term)
    {
        if (!$this->isTerm($term)) {
            return null;
        }
        [$prefix, $localName] = explode(':', $term);
        $dql = 'SELECT p FROM Omeka\Entity\Property p
        JOIN p.vocabulary v WHERE p.localName = :localName
        AND v.prefix = :prefix';
        return $this->getEntityManager()
            ->createQuery($dql)
            ->setParameters([
                'localName' => $localName,
                'prefix' => $prefix,
            ])->getOneOrNullResult();
    }

    /**
     * Get the query builder needed to get subject values.
     *
     * Note that the returned query builder does not include $qb->select().
     *
     * The $propertyId argument has three variations, depending on the desired
     * result:
     *
     * - <property-id>: Query all subject values of the specified property, e.g.
     *      123
     * - <property-id>-: Query subject values of the specified property where
     *      there is no corresponding resource template property, e.g. 123-
     * - <property-id>-<resource-template-property-id>: Query subject values of
     *      the specified property where there is a corresponding resource
     *      template property, e.g. 123-234
     *
     * @param Resource $resource
     * @param int|string|null $propertyId
     * @param string|null $resourceType
     * @param int|null $siteId
     * @return QueryBuilder
     */
    public function getSubjectValuesQueryBuilder(Resource $resource, $propertyId = null, $resourceType = null, $siteId = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->from('Omeka\Entity\Value', 'value')
            ->join('value.resource', 'resource')
            ->leftJoin('resource.resourceTemplate', 'resource_template')
            ->leftJoin('resource_template.resourceTemplateProperties', 'resource_template_property', 'WITH', 'value.property = resource_template_property.property')
            ->where($qb->expr()->eq('value.valueResource', $this->createNamedParameter($qb, $resource)));
        // Filter according to resource type and site. Note that we can only
        // filter by site when a resource type is passed because each resource
        // type requires joins that are mutually incompatible.
        switch ($resourceType) {
            case 'item_sets':
                $qb->andWhere('resource INSTANCE OF Omeka\Entity\ItemSet');
                if ($siteId) {
                    $qb->join('Omeka\Entity\SiteItemSet', 'site_item_set', 'WITH', 'resource.id = site_item_set.itemSet')
                        ->andWhere($qb->expr()->eq('site_item_set.site', $siteId));
                }
                break;
            case 'media':
                $qb->andWhere('resource INSTANCE OF Omeka\Entity\Media');
                if ($siteId) {
                    $qb->join('Omeka\Entity\Media', 'media', 'WITH', 'resource.id = media.id')
                        ->join('media.item', 'item')
                        ->join('item.sites', 'site')
                        ->andWhere($qb->expr()->eq('site.id', $siteId));
                }
                break;
            case 'items':
                $qb->andWhere('resource INSTANCE OF Omeka\Entity\Item');
                if ($siteId) {
                    $qb->join('Omeka\Entity\Item', 'item', 'WITH', 'resource.id = item.id')
                        ->join('item.sites', 'site')
                        ->andWhere($qb->expr()->eq('site.id', $siteId));
                }
                break;
            default:
                $qb->andWhere($qb->expr()->orX(
                    'resource INSTANCE OF Omeka\Entity\Item',
                    'resource INSTANCE OF Omeka\Entity\ItemSet',
                    'resource INSTANCE OF Omeka\Entity\Media'
                ));
        }
        // Filter by property and resource template property.
        if ($propertyId) {
            if (false !== strpos($propertyId, '-')) {
                $propertyIds = explode('-', $propertyId);
                $propertyId = $propertyIds[0];
                $resourceTemplatePropertyId = $propertyIds[1];
                $qb->andWhere($resourceTemplatePropertyId
                    ? $qb->expr()->eq('resource_template_property', $this->createNamedParameter($qb, $resourceTemplatePropertyId))
                    : $qb->expr()->isNull('resource_template_property')
                );
            }
            $qb->andWhere($qb->expr()->eq('value.property', $this->createNamedParameter($qb, $propertyId)));
        }
        // Need to check visibility manually here
        $services = $this->getServiceLocator();
        $acl = $services->get('Omeka\Acl');
        $identity = $services->get('Omeka\AuthenticationService')->getIdentity();
        if (!$acl->userIsAllowed('Omeka\Entity\Resource', 'view-all')) {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->eq('resource.isPublic', '1'),
                $qb->expr()->eq('resource.owner', $this->createNamedParameter($qb, $identity))
            ));
        }
        return $qb;
    }

    /**
     * Get values where the provided resource is the RDF object.
     *
     * @param Resource $resource
     * @param int $page
     * @param int $perPage
     * @param int|string|null $propertyId Filter by property ID
     * @param string|null $resourceType Filter by resource type
     * @param int|null $siteId Filter by site ID
     * @return array
     */
    public function getSubjectValues(Resource $resource, $page = null, $perPage = null, $propertyId = null, $resourceType = null, $siteId = null)
    {
        $offset = (is_numeric($page) && is_numeric($perPage)) ? (($page - 1) * $perPage) : null;
        $qb = $this->getSubjectValuesQueryBuilder($resource, $propertyId, $resourceType, $siteId)
            ->join('value.property', 'property')
            ->select([
                'value val',
                'property.id property_id',
                'resource_template_property.id resource_template_property_id',
                'property.label property_label',
                'resource_template_property.alternateLabel property_alternate_label',
            ])
            ->orderBy('property.id, resource_template_property.alternateLabel, resource.title')
            ->setMaxResults($perPage)
            ->setFirstResult($offset);
        $results = $qb->getQuery()->getResult();
        return $results;
    }

    /**
     * Get values where the provided resource is the RDF object.
     *
     * This method gets simple value data (term, id, and title) instead of the
     * value represenations. Because of this there is no need to include
     * pagination arguments, like self::getSubjectValues().
     *
     * @param Resource $resource
     * @param int|string|null $propertyId Filter by property ID
     * @param string|null $resourceType Filter by resource type
     * @param int|null $siteId Filter by site ID
     * @return array
     */
    public function getSubjectValuesSimple(Resource $resource, $propertyId = null, $resourceType = null, $siteId = null)
    {
        $qb = $this->getSubjectValuesQueryBuilder($resource, $propertyId, $resourceType, $siteId)
            ->join('value.property', 'property')
            ->join('property.vocabulary', 'vocabulary')
            ->select("CONCAT(vocabulary.prefix, ':', property.localName) term, IDENTITY(value.resource) id, resource.title title");
        return $qb->getQuery()->getResult();
    }

    /**
     * Get the total count of the provided resource's subject values.
     *
     * @param Resource $resource
     * @param int|string|null $propertyId Filter by property ID
     * @param string|null $resourceType Filter by resource type
     * @param int|null $siteId Filter by site ID
     * @return int
     */
    public function getSubjectValueTotalCount(Resource $resource, $propertyId = null, $resourceType = null, $siteId = null)
    {
        $qb = $this->getSubjectValuesQueryBuilder($resource, $propertyId, $resourceType, $siteId)
            ->select('COUNT(resource.id)');
        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Get distinct properties (predicates) where the provided resource is the RDF object.
     *
     * @param Resource $resource
     * @param string|null $resourceType Filter by resource type
     * @param int|null $siteId Filter by site ID
     * @return array
     */
    public function getSubjectValueProperties(Resource $resource, $resourceType = null, $siteId = null)
    {
        $qb = $this->getSubjectValuesQueryBuilder($resource, null, $resourceType, $siteId)
            ->join('value.property', 'property')
            ->join('property.vocabulary', 'vocabulary')
            ->select([
                "DISTINCT CONCAT(property.id, '-', COALESCE(resource_template_property.id, '')) id_concat",
                "CONCAT(vocabulary.prefix, ':', property.localName) term",
                'property.id property_id',
                'resource_template_property.id resource_template_property_id',
                'property.label property_label',
                'resource_template_property.alternateLabel property_alternate_label',
            ])
            ->orderBy('property.id, resource_template_property.id');
        $results = $qb->getQuery()->getResult();
        return $results;
    }

    public function preprocessBatchUpdate(array $data, Request $request)
    {
        $rawData = $request->getContent();
        $data = parent::preprocessBatchUpdate($data, $request);

        if (isset($rawData['o:is_public'])) {
            $data['o:is_public'] = $rawData['o:is_public'];
        }
        if (isset($rawData['o:resource_template'])) {
            $data['o:resource_template'] = $rawData['o:resource_template'];
        }
        if (isset($rawData['o:resource_class'])) {
            $data['o:resource_class'] = $rawData['o:resource_class'];
        }
        if (isset($rawData['clear_property_values'])) {
            $data['clear_property_values'] = $rawData['clear_property_values'];
        }
        if (isset($rawData['set_value_visibility'])) {
            $data['set_value_visibility'] = $rawData['set_value_visibility'];
        }

        // Add values that satisfy the bare minimum needed to identify them.
        foreach ($rawData as $term => $valueObjects) {
            if (!is_array($valueObjects)) {
                continue;
            }
            foreach ($valueObjects as $valueObject) {
                if (is_array($valueObject) && isset($valueObject['property_id'])) {
                    $data[$term][] = $valueObject;
                }
            }
        }

        return $data;
    }

    public function getFulltextOwner($resource)
    {
        return $resource->getOwner();
    }

    public function getFulltextIsPublic($resource)
    {
        return $resource->isPublic();
    }

    public function getFulltextTitle($resource)
    {
        return $resource->getTitle();
    }

    public function getFulltextText($resource)
    {
        $services = $this->getServiceLocator();
        $dataTypes = $services->get('Omeka\DataTypeManager');
        $view = $services->get('ViewRenderer');
        $criteria = Criteria::create()->where(Criteria::expr()->eq('isPublic', true));
        $texts = [];
        foreach ($resource->getValues()->matching($criteria) as $value) {
            $valueRepresentation = new ValueRepresentation($value, $services);
            $texts[] = $dataTypes->getForExtract($value)->getFulltextText($view, $valueRepresentation);
            // Add value annotation text, if any.
            $valueAnnotation = $value->getValueAnnotation();
            if ($valueAnnotation) {
                foreach ($valueAnnotation->getValues()->matching($criteria) as $value) {
                    $valueRepresentation = new ValueRepresentation($value, $services);
                    $texts[] = $dataTypes->getForExtract($value)->getFulltextText($view, $valueRepresentation);
                }
            }
        }
        return implode("\n", $texts);
    }
}
