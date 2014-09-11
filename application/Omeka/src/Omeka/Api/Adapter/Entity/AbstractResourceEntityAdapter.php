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
     * Build the values portion of the query.
     *
     * ?value[{propertyId}][{queryType}][]=foo
     *
     * @param QueryBuilder $qb
     * @param array $query
     */
    protected function buildValuesQuery(QueryBuilder $qb, array $query)
    {
        if (!isset($query['value']) || !is_array($query['value'])) {
            return;
        }
        foreach ($query['value'] as $propertyId => $queryTypes) {
            $propertyId = (int) $propertyId;
            foreach ($queryTypes as $queryType => $values) {
                if (!is_array($values)) {
                    continue;
                }
                foreach ($values as $value) {
                    $valuesJoin = $this->getEntityClass() . '.values';
                    $valuesAlias = $this->getToken();
                    $valuePlaceholder = $this->getToken();
                    // Is equal
                    if ('equal' == $queryType) {
                        $qb->innerJoin(
                            $valuesJoin, $valuesAlias, 'WITH',
                            $qb->expr()->eq(
                                "$valuesAlias.property",
                                $propertyId
                            )
                        );
                        $qb->andWhere($qb->expr()->eq(
                            "$valuesAlias.value",
                            ":$valuePlaceholder"
                        ));
                        $qb->setParameter($valuePlaceholder, $value);
                    // Is not equal
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
                                    $propertyId
                                )
                            )
                        );
                        $qb->andWhere($qb->expr()->isNull(
                            "$valuesAlias.value"
                        ));
                        $qb->setParameter($valuePlaceholder, $value);
                    // Contains
                    } elseif ('contain' == $queryType) {
                        $qb->innerJoin(
                            $valuesJoin, $valuesAlias, 'WITH',
                            $qb->expr()->eq(
                                "$valuesAlias.property",
                                $propertyId
                            )
                        );
                        $qb->andWhere($qb->expr()->like(
                            "$valuesAlias.value",
                            ":$valuePlaceholder"
                        ));
                        $qb->setParameter($valuePlaceholder, "%$value%");
                    // Does not contain
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
                                    $propertyId
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
