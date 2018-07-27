<?php
namespace Omeka\Api\Adapter;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Entity\Resource;
use Omeka\Stdlib\ErrorStore;
use Omeka\Stdlib\Message;

abstract class AbstractResourceEntityAdapter extends AbstractEntityAdapter
{
    /**
     * {@inheritDoc}
     */
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

        if (isset($query['owner_id'])) {
            $userAlias = $this->createAlias();
            $qb->innerJoin(
                $this->getEntityClass() . '.owner',
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
                $this->getEntityClass() . '.resourceClass',
                $resourceClassAlias
            );
            $qb->andWhere($qb->expr()->eq(
                "$resourceClassAlias.label",
                $this->createNamedParameter($qb, $query['resource_class_label']))
            );
        }

        if (isset($query['resource_class_id']) && is_numeric($query['resource_class_id'])) {
            $resourceClassAlias = $this->createAlias();
            $qb->innerJoin(
                $this->getEntityClass() . '.resourceClass',
                $resourceClassAlias
            );
            $qb->andWhere($qb->expr()->eq(
                "$resourceClassAlias.id",
                $this->createNamedParameter($qb, $query['resource_class_id']))
            );
        }

        if (isset($query['resource_template_id']) && is_numeric($query['resource_template_id'])) {
            $resourceTemplateAlias = $this->createAlias();
            $qb->innerJoin(
                $this->getEntityClass() . '.resourceTemplate',
                $resourceTemplateAlias
            );
            $qb->andWhere($qb->expr()->eq(
                "$resourceTemplateAlias.id",
                $this->createNamedParameter($qb, $query['resource_template_id']))
            );
        }

        if (isset($query['is_public'])) {
            $qb->andWhere($qb->expr()->eq(
                $this->getEntityClass() . '.isPublic',
                $this->createNamedParameter($qb, (bool) $query['is_public'])
            ));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function sortQuery(QueryBuilder $qb, array $query)
    {
        if (is_string($query['sort_by'])) {
            $property = $this->getPropertyByTerm($query['sort_by']);
            $entityClass = $this->getEntityClass();
            if ($property) {
                $valuesAlias = $this->createAlias();
                $qb->leftJoin(
                    "$entityClass.values", $valuesAlias,
                    'WITH', $qb->expr()->eq("$valuesAlias.property", $property->getId())
                );
                $qb->addOrderBy(
                    "GROUP_CONCAT($valuesAlias.value ORDER BY $valuesAlias.id)",
                    $query['sort_order']
                );
            } elseif ('resource_class_label' == $query['sort_by']) {
                $resourceClassAlias = $this->createAlias();
                $qb->leftJoin("$entityClass.resourceClass", $resourceClassAlias)
                    ->addOrderBy("$resourceClassAlias.label", $query['sort_order']);
            } elseif ('owner_name' == $query['sort_by']) {
                $ownerAlias = $this->createAlias();
                $qb->leftJoin("$entityClass.owner", $ownerAlias)
                    ->addOrderBy("$ownerAlias.name", $query['sort_order']);
            } else {
                parent::sortQuery($qb, $query);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
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
                        'The "%s" resource template requires a "%s" value', // @translate
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
     *
     * @param QueryBuilder $qb
     * @param array $query
     */
    protected function buildPropertyQuery(QueryBuilder $qb, array $query)
    {
        if (!isset($query['property']) || !is_array($query['property'])) {
            return;
        }
        $valuesJoin = $this->getEntityClass() . '.values';
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
            $joiner = isset($queryRow['joiner']) ? $queryRow['joiner'] : null;
            $value = isset($queryRow['text']) ? $queryRow['text'] : null;

            if (!$value && $queryType !== 'nex' && $queryType !== 'ex') {
                continue;
            }

            $valuesAlias = $this->createAlias();
            $positive = true;

            switch ($queryType) {
                case 'neq':
                    $positive = false;
                case 'eq':
                    $param = $this->createNamedParameter($qb, $value);
                    $predicateExpr = $qb->expr()->orX(
                        $qb->expr()->eq("$valuesAlias.value", $param),
                        $qb->expr()->eq("$valuesAlias.uri", $param)
                    );
                    break;
                case 'nin':
                    $positive = false;
                case 'in':
                    $param = $this->createNamedParameter($qb, "%$value%");
                    $predicateExpr = $qb->expr()->orX(
                        $qb->expr()->like("$valuesAlias.value", $param),
                        $qb->expr()->like("$valuesAlias.uri", $param)
                    );
                    break;
                case 'nres':
                    $positive = false;
                case 'res':
                    $predicateExpr = $qb->expr()->eq(
                        "$valuesAlias.valueResource",
                        $this->createNamedParameter($qb, $value)
                    );
                    break;
                case 'nex':
                    $positive = false;
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
    protected function getPropertyByTerm($term)
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
        $findBy = ['valueResource' => $resource];
        if ($property) {
            $findBy['property'] = $property;
        }

        // Need to check visibility manually here
        $services = $this->getServiceLocator();
        $identity = $services->get('Omeka\AuthenticationService')->getIdentity();
        $acl = $services->get('Omeka\Acl');

        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->select('v')
            ->from('Omeka\Entity\Value', 'v')
            ->join('v.resource', 'r')
            ->where($qb->expr()->eq('v.valueResource', $this->createNamedParameter($qb, $resource)));

        if (!$acl->userIsAllowed('Omeka\Entity\Resource', 'view-all')) {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->eq('r.isPublic', '1'),
                $qb->expr()->eq('r.owner', $this->createNamedParameter($qb, $identity))
            ));
        }

        if ($property) {
            $qb->where('v.property', $this->createNamedParameter($qb, $property));
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
        $dql = 'SELECT COUNT(v.id) FROM Omeka\Entity\Value v WHERE v.valueResource = :resource';
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

    /**
     * {@inheritDoc}
     */
    public function preprocessBatchUpdate(array $data, Request $request)
    {
        $rawData = $request->getContent();

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
}
