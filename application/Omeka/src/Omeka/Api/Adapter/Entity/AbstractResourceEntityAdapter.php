<?php
namespace Omeka\Api\Adapter\Entity;

use Doctrine\ORM\QueryBuilder;
use Omeka\Model\Entity\EntityInterface;

abstract class AbstractResourceEntityAdapter extends AbstractEntityAdapter
{
    /**
     * {@inheritDoc}
     */
    public function buildQuery(QueryBuilder $qb, array $query)
    {
        $this->buildValueQuery($qb, $query);
        $this->buildPropertyQuery($qb, $query);
        $this->buildHasPropertyQuery($qb, $query);
    }

    /**
     * {@inheritDoc}
     */
    public function sortQuery(QueryBuilder $qb, array $query)
    {
        if (isset($query['sort_by'])) {
            $property = $this->getPropertyByTerm($query['sort_by']);
            if ($property) {
                $qb->leftJoin(
                    $this->getEntityClass() . '.values',
                    'omeka_order_values',
                    'WITH',
                    $qb->expr()->eq(
                        'omeka_order_values.property',
                        $property->getId()
                    )
                );
                $qb->orderBy('omeka_order_values.value', $query['sort_order']);
            } elseif ('resource_class_label' == $query['sort_by']) {
                $qb ->leftJoin(
                    $this->getEntityClass() . '.resourceClass',
                    'omeka_order'
                )->orderBy('omeka_order.label', $query['sort_order']);
            } else {
                parent::sortQuery($qb, $query);
            }
        }
    }

    /**
     * Hydrate this resource's values.
     *
     * @param array $data
     * @param EntityInterface $entity
     */
    protected function hydrateValues(array $data, EntityInterface $entity)
    {
        $valueHydrator = new ValueHydrator($this);
        $valueHydrator->hydrate($data, $entity);
    }

    /**
     * Build query on value.
     *
     * Query types:
     *   + value[eq][]={value}:  has exact value
     *   + value[neq][]={value}: does not have exact value
     *   + value[in][]={value}:  contains value
     *   + value[nin][]={value}: does not contain value
     *
     * @param QueryBuilder $qb
     * @param array $query
     */
    protected function buildValueQuery(QueryBuilder $qb, array $query)
    {
        if (!isset($query['value']) || !is_array($query['value'])) {
            return;
        }
        $valuesJoin = $this->getEntityClass() . '.values';
        foreach ($query['value'] as $queryType => $values) {
            if (!is_array($values)) {
                continue;
            }
            foreach ($values as $value) {
                $valuesAlias = $this->getToken();
                $valuePlaceholder = $this->getToken();
                if ('eq' == $queryType) {
                    $qb->innerJoin($valuesJoin, $valuesAlias);
                    $qb->andWhere($qb->expr()->eq(
                        "$valuesAlias.value",
                        ":$valuePlaceholder"
                    ));
                    $qb->setParameter($valuePlaceholder, $value);
                } elseif ('neq' == $queryType) {
                    $qb->leftJoin(
                        $valuesJoin, $valuesAlias, 'WITH',
                        $qb->expr()->eq(
                            "$valuesAlias.value",
                            ":$valuePlaceholder"
                        )
                    );
                    $qb->andWhere($qb->expr()->isNull(
                        "$valuesAlias.value"
                    ));
                    $qb->setParameter($valuePlaceholder, $value);
                } elseif ('in' == $queryType) {
                    $qb->innerJoin($valuesJoin, $valuesAlias);
                    $qb->andWhere($qb->expr()->like(
                        "$valuesAlias.value",
                        ":$valuePlaceholder"
                    ));
                    $qb->setParameter($valuePlaceholder, "%$value%");
                } elseif ('nin' == $queryType) {
                    $qb->leftJoin(
                        $valuesJoin, $valuesAlias, 'WITH',
                        $qb->expr()->like(
                            "$valuesAlias.value",
                            ":$valuePlaceholder"
                        )
                    );
                    $qb->andWhere($qb->expr()->isNull(
                        "$valuesAlias.value"
                    ));
                    $qb->setParameter($valuePlaceholder, "%$value%");
                }
            }
        }
    }

    /**
     * Build query by property.
     *
     * Query types:
     *   + property[{pid}][eq][]={value}:  has exact value by property
     *   + property[{pid}][neq][]={value}: does not have exact value by property
     *   + property[{pid}][in][]={value}:  contains value by property
     *   + property[{pid}][nin][]={value}: does not contain value by property
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
                    $valuesAlias = $this->getToken();
                    $valuePlaceholder = $this->getToken();
                    if ('eq' == $queryType) {
                        $qb->innerJoin(
                            $valuesJoin, $valuesAlias, 'WITH',
                            $qb->expr()->eq(
                                "$valuesAlias.property",
                                (int) $propertyId
                            )
                        );
                        $qb->andWhere($qb->expr()->eq(
                            "$valuesAlias.value",
                            ":$valuePlaceholder"
                        ));
                        $qb->setParameter($valuePlaceholder, $value);
                    } elseif ('neq' == $queryType) {
                        $qb->leftJoin(
                            $valuesJoin, $valuesAlias, 'WITH',
                            $qb->expr()->andX(
                                $qb->expr()->eq(
                                    "$valuesAlias.value",
                                    ":$valuePlaceholder"
                                ),
                                $qb->expr()->eq(
                                    "$valuesAlias.property",
                                    (int) $propertyId
                                )
                            )
                        );
                        $qb->andWhere($qb->expr()->isNull(
                            "$valuesAlias.value"
                        ));
                        $qb->setParameter($valuePlaceholder, $value);
                    } elseif ('in' == $queryType) {
                        $qb->innerJoin(
                            $valuesJoin, $valuesAlias, 'WITH',
                            $qb->expr()->eq(
                                "$valuesAlias.property",
                                (int) $propertyId
                            )
                        );
                        $qb->andWhere($qb->expr()->like(
                            "$valuesAlias.value",
                            ":$valuePlaceholder"
                        ));
                        $qb->setParameter($valuePlaceholder, "%$value%");
                    } elseif ('nin' == $queryType) {
                        $qb->leftJoin(
                            $valuesJoin, $valuesAlias, 'WITH',
                            $qb->expr()->andX(
                                $qb->expr()->like(
                                    "$valuesAlias.value",
                                    ":$valuePlaceholder"
                                ),
                                $qb->expr()->eq(
                                    "$valuesAlias.property",
                                    (int) $propertyId
                                )
                            )
                        );
                        $qb->andWhere($qb->expr()->isNull(
                            "$valuesAlias.value"
                        ));
                        $qb->setParameter($valuePlaceholder, "%$value%");
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
                $valuesAlias = $this->getToken();
                $qb->innerJoin(
                    $valuesJoin, $valuesAlias, 'WITH',
                    $qb->expr()->eq(
                        "$valuesAlias.property",
                        (int) $propertyId
                    )
                );
            } else {
                $valuesAlias = $this->getToken();
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
        $dql = 'SELECT p FROM Omeka\Model\Entity\Property p
        JOIN p.vocabulary v WHERE p.localName = :localName
        AND v.prefix = :prefix';
        return $this->getEntityManager()
            ->createQuery($dql)
            ->setParameters(array(
                'localName' => $localName,
                'prefix' => $prefix
            ))->getOneOrNullResult();
    }

    /**
     * Determine whether a string is a valid JSON-LD term.
     *
     * @param string $term
     * @return bool
     */
    protected function isTerm($term)
    {
        return (bool) preg_match('/^[a-z0-9-_]+:[a-z0-9-_]+$/i', $term);
    }
}
