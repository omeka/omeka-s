<?php
namespace Omeka\Api\Adapter;

use Doctrine\DBAL\Connection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr\Join;
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
     *   - property[{index}][property]: property ID or term or array of property IDs or terms
     *   - property[{index}][text]: search text or array of texts or values
     *   - property[{index}][type]: search type
     *     - eq: is exactly
     *     - neq: is not exactly
     *     - in: contains
     *     - nin: does not contain
     *     - res: has resource #id
     *     - nres: has no resource #id
     *     - ex: has any value
     *     - nex: has no value
     *     - lex: is a linked resource
     *     - nlex: is not a linked resource
     *     - lres: is linked with resource #id
     *     - nlres: is not linked with resource #id
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

        // @see \Doctrine\ORM\QueryBuilder::expr().
        $expr = $qb->expr();
        $entityManager = $this->getEntityManager();

        $queryTypes = [
            'eq' => null,
            'neq' => null,
            'in' => null,
            'nin' => null,
            'res' => null,
            'nres' => null,
            'ex' => null,
            'nex' => null,
            'lex' => null,
            'nlex' => null,
            'lres' => null,
            'nlres' => null,
        ];

        $arrayValueQueryTypes = [
            'res',
            'nres',
            'lres',
            'nlres',
        ];

        $intValueQueryTypes = [
            'res',
            'nres',
            'lres',
            'nlres',
        ];

        $withoutValueQueryTypes = [
            'ex',
            'nex',
            'lex',
            'nlex',
        ];

        $subjectQueryTypes = [
            'lex',
            'nlex',
            'lres',
            'nlres',
        ];

        foreach ($query['property'] as $queryRow) {
            if (!(
                is_array($queryRow)
                && array_key_exists('type', $queryRow)
            )) {
                continue;
            }

            $queryType = $queryRow['type'];
            if (!array_key_exists($queryType, $queryTypes)) {
                continue;
            }

            $value = $queryRow['text'] ?? null;
            // Quick check of value.
            // An empty string "" is not a value, but "0" is a value.
            if (in_array($queryType, $withoutValueQueryTypes, true)) {
                $value = null;
            }
            // Check array of values.
            elseif (in_array($queryType, $arrayValueQueryTypes, true)) {
                if ((is_array($value) && !count($value)) || !strlen((string) $value)) {
                    continue;
                }
                if (!is_array($value)) {
                    $value = [$value];
                }
                $value = in_array($queryType, $intValueQueryTypes)
                    ? array_unique(array_map('intval', $value))
                    : array_unique(array_filter(array_map('trim', array_map('strval', $value)), 'strlen'));
            }
            // The value should be a scalar in all other cases.
            elseif (is_array($value) || !strlen((string) $value)) {
                continue;
            }

            $joiner = $queryRow['joiner'] ?? null;

            $valuesAlias = $this->createAlias();
            $positive = true;

            switch ($queryType) {
                case 'neq':
                    $positive = false;
                    // no break.
                case 'eq':
                    $param = $this->createNamedParameter($qb, $value);
                    $subqueryAlias = $this->createAlias();
                    $subquery = $entityManager->createQueryBuilder()
                        ->select("$subqueryAlias.id")
                        ->from('Omeka\Entity\Resource', $subqueryAlias)
                        ->where($expr->eq("$subqueryAlias.title", $param));
                    $predicateExpr = $expr->orX(
                        $expr->in("$valuesAlias.valueResource", $subquery->getDQL()),
                        $expr->eq("$valuesAlias.value", $param),
                        $expr->eq("$valuesAlias.uri", $param)
                    );
                    break;

                case 'nin':
                    $positive = false;
                    // no break.
                case 'in':
                    $param = $this->createNamedParameter($qb, "%$value%");
                    $subqueryAlias = $this->createAlias();
                    $subquery = $entityManager->createQueryBuilder()
                        ->select("$subqueryAlias.id")
                        ->from('Omeka\Entity\Resource', $subqueryAlias)
                        ->where($expr->like("$subqueryAlias.title", $param));
                    $predicateExpr = $expr->orX(
                        $expr->in("$valuesAlias.valueResource", $subquery->getDQL()),
                        $expr->like("$valuesAlias.value", $param),
                        $expr->like("$valuesAlias.uri", $param)
                    );
                    break;

                case 'nres':
                    $positive = false;
                    // no break.
                case 'res':
                    if (count($value) <= 1) {
                        $param = $this->createNamedParameter($qb, (int) reset($value));
                        $predicateExpr = $expr->eq("$valuesAlias.valueResource", $param);
                    } else {
                        $param = $this->createNamedParameter($qb, $value);
                        $qb->setParameter(substr($param, 1), $value, Connection::PARAM_INT_ARRAY);
                        $predicateExpr = $expr->in("$valuesAlias.valueResource", $param);
                    }
                    break;

                case 'nex':
                    $positive = false;
                    // no break.
                case 'ex':
                    $predicateExpr = $expr->isNotNull("$valuesAlias.id");
                    break;

                // The linked resources (subject values) use the same sub-query.
                case 'nlex':
                    // For consistency, "nlex" is the reverse of "lex" even when
                    // a resource is linked with a public and a private resource.
                    // A private linked resource is not linked for an anonymous.
                case 'nlres':
                    $positive = false;
                    // no break.
                case 'lex':
                case 'lres':
                    $subValuesAlias = $this->createAlias();
                    $subResourceAlias = $this->createAlias();
                    // Use a subquery so rights are automatically managed.
                    $subQb = $entityManager->createQueryBuilder()
                        ->select("IDENTITY($subValuesAlias.valueResource)")
                        ->from(\Omeka\Entity\Value::class, $subValuesAlias)
                        ->innerJoin("$subValuesAlias.resource", $subResourceAlias)
                        ->where($expr->isNotNull("$subValuesAlias.valueResource"));
                    // Warning: the property check should be done on subjects,
                    // so the predicate expression is finalized below.
                    if (is_array($value)) {
                        // In fact, "lres" is the list of linked resources.
                        if (count($value) <= 1) {
                            $param = $this->createNamedParameter($qb, (int) reset($value));
                            $subQb->andWhere($expr->eq("$subValuesAlias.resource", $param));
                        } else {
                            $param = $this->createNamedParameter($qb, $value);
                            $qb->setParameter(substr($param, 1), $value, Connection::PARAM_INT_ARRAY);
                            $subQb->andWhere($expr->in("$subValuesAlias.resource", $param));
                        }
                    }
                    break;

                default:
                    continue 2;
            }

            $joinConditions = [];

            // Narrow to specific properties, if one or more are selected.
            $propertyIds = $queryRow['property'] ?? null;
            // Properties may be an array with an empty value (any property) in
            // advanced form, so remove empty strings from it, in which case the
            // check should be skipped.
            if (is_array($propertyIds) && in_array('', $propertyIds, true)) {
                $propertyIds = [];
            }
            if ($propertyIds) {
                $propertyIds = array_values(array_unique($this->getPropertyIds($propertyIds)));
                if ($propertyIds) {
                    // For queries on subject values, the properties should be
                    // checked against the sub-query.
                    if (in_array($queryType, $subjectQueryTypes)) {
                        $subQb
                            ->andWhere(count($propertyIds) < 2
                                ? $expr->eq("$subValuesAlias.property", reset($propertyIds))
                                : $expr->in("$subValuesAlias.property", $propertyIds)
                            );
                    } else {
                        $joinConditions[] = count($propertyIds) < 2
                            ? $expr->eq("$valuesAlias.property", reset($propertyIds))
                            : $expr->in("$valuesAlias.property", $propertyIds);
                    }
                } else {
                    // Don't return results for this part for fake properties.
                    $joinConditions[] = $expr->eq("$valuesAlias.property", 0);
                }
            }

            // Finalize predicate expression on subject values.
            if (in_array($queryType, $subjectQueryTypes)) {
                $predicateExpr = $expr->in("$valuesAlias.resource", $subQb->getDQL());
            }

            if ($positive) {
                $whereClause = '(' . $predicateExpr . ')';
            } else {
                $joinConditions[] = $predicateExpr;
                $whereClause = $expr->isNull("$valuesAlias.id");
            }

            if ($joinConditions) {
                $qb->leftJoin($valuesJoin, $valuesAlias, Join::WITH, $expr->andX(...$joinConditions));
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
        list($prefix, $localName) = explode(':', $term);
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
     * Get one or more property ids by JSON-LD terms or by numeric ids.
     *
     * @param array|int|string|null $termsOrIds One or multiple ids or terms.
     * @return int[] The property ids matching terms or ids, or all properties
     * by terms.
     */
    public function getPropertyIds($termsOrIds = null): array
    {
        static $propertiesByTerms;
        static $propertiesByTermsAndIds;

        if (is_null($propertiesByTermsAndIds)) {
            $connection = $this->getServiceLocator()->get('Omeka\Connection');
            $qb = $connection->createQueryBuilder();
            $qb
                ->select(
                    'DISTINCT CONCAT(vocabulary.prefix, ":", property.local_name) AS term',
                    'property.id AS id',
                    // Required with only_full_group_by.
                    'vocabulary.id'
                )
                ->from('property', 'property')
                ->innerJoin('property', 'vocabulary', 'vocabulary', 'property.vocabulary_id = vocabulary.id')
                ->orderBy('vocabulary.id', 'asc')
                ->addOrderBy('property.id', 'asc')
            ;
            $propertiesByTerms = array_map('intval', $connection->executeQuery($qb)->fetchAllKeyValue());
            $propertiesByTermsAndIds = array_replace($propertiesByTerms, array_combine($propertiesByTerms, $propertiesByTerms));
        }

        if (is_null($termsOrIds)) {
            return $propertiesByTerms;
        }

        if (is_scalar($termsOrIds)) {
            return isset($propertiesByTermsAndIds[$termsOrIds])
                ? [$termsOrIds => $propertiesByTermsAndIds[$termsOrIds]]
                : [];
        }

        return array_intersect_key($propertiesByTermsAndIds, array_flip($termsOrIds));
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
        $offset = (is_numeric($page) && is_numeric($perPage))
            ? (($page - 1) * $perPage)
            : null;

        // Need to check visibility manually here
        $services = $this->getServiceLocator();
        $identity = $services->get('Omeka\AuthenticationService')->getIdentity();
        $acl = $services->get('Omeka\Acl');

        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->select('v')
            ->from('Omeka\Entity\Value', 'v')
            ->join('v.resource', 'r')
            ->where($qb->expr()->eq('v.valueResource', $this->createNamedParameter($qb, $resource)))
            // Limit subject values to those belonging to primary resources.
            ->andWhere($qb->expr()->orX(
                'r INSTANCE OF Omeka\Entity\Item',
                'r INSTANCE OF Omeka\Entity\ItemSet',
                'r INSTANCE OF Omeka\Entity\Media'
            ));

        if (!$acl->userIsAllowed('Omeka\Entity\Resource', 'view-all')) {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->eq('r.isPublic', '1'),
                $qb->expr()->eq('r.owner', $this->createNamedParameter($qb, $identity))
            ));
        }

        if ($property) {
            $qb->andWhere($qb->expr()->eq('v.property', $this->createNamedParameter($qb, $property)));
        }

        $qb->setMaxResults($perPage);
        $qb->setFirstResult($offset);

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
        }
        return implode("\n", $texts);
    }
}
