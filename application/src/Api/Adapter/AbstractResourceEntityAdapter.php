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
        $this->buildHasPropertyQuery($qb, $query);

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
        $append = $request->getOperation() === Request::UPDATE
            && $request->isPartial();
        $valueHydrator = new ValueHydrator($this);
        $valueHydrator->hydrate($data, $entity, $append);

        // o:owner
        $this->hydrateOwner($request, $entity);

        // o:resource_class
        $this->hydrateResourceClass($request, $entity);

        // o:resource_template
        $this->hydrateResourceTemplate($request, $entity);
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
     * Build query on value (optionally by property).
     *
     * Query types:
     *   + property[{pid}][eq][]={value}:  has exact value
     *   + property[{pid}][neq][]={value}: does not have exact value
     *   + property[{pid}][in][]={value}:  contains value
     *   + property[{pid}][nin][]={value}: does not contain value
     *
     * If {pid} is zero/empty, queries are against all values. Otherwise, query results are limited
     * to the given property.
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
        foreach ($query['property'] as $propertyId => $queryTypes) {
            if (!is_array($queryTypes)) {
                continue;
            }
            foreach ($queryTypes as $queryType => $values) {
                if (!is_array($values)) {
                    continue;
                }
                foreach ($values as $value) {
                    if (!is_string($value) || $value === '') {
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
                        default:
                            continue;
                    }

                    // Narrow to specific property, if one is selected
                    if ($propertyId) {
                        $predicateExpr = $qb->expr()->andX(
                            $predicateExpr,
                            $qb->expr()->eq("$valuesAlias.property", (int) $propertyId)
                        );
                    }

                    if ($positive) {
                        $qb->innerJoin($valuesJoin, $valuesAlias, 'WITH', $predicateExpr);
                    } else {
                        $qb->leftJoin($valuesJoin, $valuesAlias, 'WITH', $predicateExpr);
                        $qb->andWhere($qb->expr()->isNull("$valuesAlias.value"));
                    }
                }
            }
        }
    }

    /**
     * Build query by has property.
     *
     * Query types:
     *   + has_property[{pid}]=1: has any value for property
     *   + has_property[{pid}]=0: has no value for property
     *
     * @param QueryBuilder $qb
     * @param array $query
     */
    protected function buildHasPropertyQuery(QueryBuilder $qb, array $query)
    {
        if (!isset($query['has_property']) || !is_array($query['has_property'])) {
            return;
        }
        $valuesJoin = $this->getEntityClass() . '.values';
        foreach ($query['has_property'] as $propertyId => $hasProperty) {
            if ((bool) $hasProperty) {
                $valuesAlias = $this->createAlias();
                $qb->innerJoin(
                    $valuesJoin, $valuesAlias, 'WITH',
                    $qb->expr()->eq(
                        "$valuesAlias.property",
                        (int) $propertyId
                    )
                );
            } else {
                $valuesAlias = $this->createAlias();
                $qb->leftJoin(
                    $valuesJoin, $valuesAlias, 'WITH',
                    $qb->expr()->eq(
                        "$valuesAlias.property",
                        (int) $propertyId
                    )
                );
                $qb->andWhere($qb->expr()->isNull(
                    "$valuesAlias.property"
                ));
            }
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
                'prefix' => $prefix
            ])->getOneOrNullResult();
    }

    /**
     * Get values where the provided resource is the RDF object.
     *
     * @param Resource $resource
     * @return array
     */
    public function getSubjectValues(Resource $resource)
    {
        return $this->getEntityManager()
            ->getRepository('Omeka\Entity\Value')
            ->findBy(['valueResource' => $resource]);
    }
}
