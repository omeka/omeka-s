<?php
namespace Omeka\Api\Adapter\Entity;

use Doctrine\ORM\QueryBuilder;
use Omeka\Model\Entity\EntityInterface;

abstract class AbstractResourceEntityAdapter extends AbstractEntityAdapter
{
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
     * Builds queries on value.
     *
     * There are several query types:
     *   + value[empty][]={propertyId}:             has no value for property
     *   + value[nempty][]={propertyId}:            has any value for property
     *   + value[equal][]={value}:                  has exact value
     *   + value[nequal][]={value}:                 has no exact value
     *   + value[contain][]={value}:                contains value
     *   + value[ncontain][]={value}:               does not contain value
     *   + value[{propertyId}][equal][]={value}:    has exact value for property
     *   + value[{propertyId}][nequal][]={value}:   has no exact value for property
     *   + value[{propertyId}][contain][]={value}:  contains value for property
     *   + value[{propertyId}][ncontain][]={value}: does not contain value for property
     *
     * @param QueryBuilder $qb
     * @param array $query
     */
    protected function buildValuesQuery(QueryBuilder $qb, array $query)
    {
        if (!isset($query['value']) || !is_array($query['value'])) {
            return;
        }
        $valuesJoin = $this->getEntityClass() . '.values';
        foreach ($query['value'] as $propertyId => $queryTypes) {
            if (!is_array($queryTypes)) {
                continue;
            }
            foreach ($queryTypes as $queryType => $values) {
                // equal
                if ('equal' == $propertyId) {
                    $valuesAlias = $this->getToken();
                    $valuePlaceholder = $this->getToken();
                    $qb->innerJoin($valuesJoin, $valuesAlias);
                    $qb->andWhere($qb->expr()->eq(
                        "$valuesAlias.value",
                        ":$valuePlaceholder"
                    ));
                    $qb->setParameter($valuePlaceholder, $values);
                // nequal
                } elseif ('nequal' == $propertyId) {
                    $valuesAlias = $this->getToken();
                    $valuePlaceholder = $this->getToken();
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
                    $qb->setParameter($valuePlaceholder, $values);
                // contain
                } elseif ('contain' == $propertyId) {
                    $valuesAlias = $this->getToken();
                    $valuePlaceholder = $this->getToken();
                    $qb->innerJoin($valuesJoin, $valuesAlias);
                    $qb->andWhere($qb->expr()->like(
                        "$valuesAlias.value",
                        ":$valuePlaceholder"
                    ));
                    $qb->setParameter($valuePlaceholder, "%$values%");
                // ncontain
                } elseif ('ncontain' == $propertyId) {
                    $valuesAlias = $this->getToken();
                    $valuePlaceholder = $this->getToken();
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
                    $qb->setParameter($valuePlaceholder, "%$values%");
                // empty
                } elseif ('empty' == $propertyId) {
                    $valuesAlias = $this->getToken();
                    $qb->leftJoin(
                        $valuesJoin, $valuesAlias, 'WITH',
                        $qb->expr()->eq(
                            "$valuesAlias.property",
                            (int) $values
                        )
                    );
                    $qb->andWhere($qb->expr()->isNull(
                        "$valuesAlias.property"
                    ));
                // nempty
                } elseif ('nempty' == $propertyId) {
                    $valuesAlias = $this->getToken();
                    $qb->innerJoin(
                        $valuesJoin, $valuesAlias, 'WITH',
                        $qb->expr()->eq(
                            "$valuesAlias.property",
                            (int) $values
                        )
                    );
                } elseif (is_array($values)) {
                    foreach ($values as $value) {
                        $valuesAlias = $this->getToken();
                        $valuePlaceholder = $this->getToken();
                        // equal
                        if ('equal' == $queryType) {
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
                        // nequal
                        } elseif ('nequal' == $queryType) {
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
                        // contain
                        } elseif ('contain' == $queryType) {
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
                        // ncontain
                        } elseif ('ncontain' == $queryType) {
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
    }

    /**
     * Get a property entity by JSON-LD term.
     *
     * @param string $term
     * @return EntityInterface
     */
    protected function getPropertyByTerm($term)
    {
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
        if (preg_match('/[a-z0-9-_]+:[a-z0-9-_]+/i', $term)) {
            return true;
        }
        return false;
    }
}
