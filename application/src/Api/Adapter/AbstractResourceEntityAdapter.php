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
                    $param = $this->createNamedParameter($qb, "%$value%");
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
                    $param = $this->createNamedParameter($qb, "$value%");
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
                    $param = $this->createNamedParameter($qb, "%$value");
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
     * @param Resource $resource
     * @param int|null $property
     * @return QueryBuilder
     */
    public function getSubjectValuesQueryBuilder(Resource $resource, $property = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->from('Omeka\Entity\Value', 'v')
            ->join('v.resource', 'r')
            ->where($qb->expr()->eq('v.valueResource', $this->createNamedParameter($qb, $resource)))
            // Limit subject values to those belonging to primary resources.
            ->andWhere($qb->expr()->orX(
                'r INSTANCE OF Omeka\Entity\Item',
                'r INSTANCE OF Omeka\Entity\ItemSet',
                'r INSTANCE OF Omeka\Entity\Media'
            ));
        if ($property) {
            $qb->andWhere($qb->expr()->eq('v.property', $this->createNamedParameter($qb, $property)));
        }
        // Need to check visibility manually here
        $services = $this->getServiceLocator();
        $acl = $services->get('Omeka\Acl');
        $identity = $services->get('Omeka\AuthenticationService')->getIdentity();
        if (!$acl->userIsAllowed('Omeka\Entity\Resource', 'view-all')) {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->eq('r.isPublic', '1'),
                $qb->expr()->eq('r.owner', $this->createNamedParameter($qb, $identity))
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
     * @param int $property Filter by property ID
     * @return array
     */
    public function getSubjectValues(Resource $resource, $page = null, $perPage = null, $property = null)
    {
        $offset = (is_numeric($page) && is_numeric($perPage)) ? (($page - 1) * $perPage) : null;
        $qb = $this->getSubjectValuesQueryBuilder($resource, $property)
            ->select([
                'v value',
                'p.id property_id',
                'rtp.id resource_template_property_id',
                'p.label property_label',
                'rtp.alternateLabel property_alternate_label',
            ])
            ->join('v.property', 'p')
            ->leftJoin('r.resourceTemplate', 'rt')
            ->leftJoin('rt.resourceTemplateProperties', 'rtp', 'WITH', 'p = rtp.property')
            ->orderBy('p.label, p.id, rtp.alternateLabel, r.title')
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
     * @param int $property Filter by property ID
     * @return array
     */
    public function getSubjectValuesSimple(Resource $resource, $property = null)
    {
        $qb = $this->getSubjectValuesQueryBuilder($resource, $property)
            ->select("CONCAT(y.prefix, ':', p.localName) term, IDENTITY(v.resource) id, r.title title")
            ->join('v.property', 'p')
            ->join('p.vocabulary', 'y');
        return $qb->getQuery()->getResult();
    }

    /**
     * Get the total count of the provided resource's subject values.
     *
     * @param Resource $resource
     * @param int $property Filter by property ID
     * @return int
     */
    public function getSubjectValueTotalCount(Resource $resource, $property = null)
    {
        $dql = 'SELECT COUNT(r.id) FROM Omeka\Entity\Value v JOIN v.resource r WHERE v.valueResource = :resource';
        $params = ['resource' => $resource];
        if ($property) {
            $dql .= ' AND v.property = :property';
            $params['property'] = $property;
        }
        return $this->getEntityManager()
            ->createQuery($dql)
            ->setParameters($params)
            ->getSingleScalarResult();
    }

    /**
     * Get distinct properties (predicates) where the provided resource is the RDF object.
     *
     * @param Resource $resource
     * @return array
     */
    public function getSubjectValueProperties(Resource $resource)
    {
        $dql = 'SELECT p FROM Omeka\Entity\Property p JOIN p.values v WITH v.valueResource = :resource GROUP BY p.id ORDER BY p.label';
        return $this->getEntityManager()
            ->createQuery($dql)
            ->setParameters(['resource' => $resource])
            ->getResult();
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
