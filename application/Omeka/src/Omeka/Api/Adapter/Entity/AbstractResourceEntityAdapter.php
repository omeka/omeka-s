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
     * @param QueryBuilder $qb
     * @param array $query
     */
    protected function buildValuesQuery(QueryBuilder $qb, array $query)
    {
        if (!isset($query['value'])) {
            return;
        }

        // Is equal
        if (isset($query['value']['equal']) && is_array($query['value']['equal'])) {
            foreach ($query['value']['equal'] as $propertyId => $values) {
                if (!is_array($values)) {
                    continue;
                }
                foreach ($values as $value) {
                    $valuesAlias = $this->getToken();
                    $valuePlaceholder = $this->getToken();
                    $qb->innerJoin(
                        $this->getEntityClass() . '.values', $valuesAlias, 'WITH',
                        $qb->expr()->eq(
                            "$valuesAlias.property", (int) $propertyId
                        )
                    );
                    $qb->andWhere($qb->expr()->eq(
                        "$valuesAlias.value", ":$valuePlaceholder"
                    ));
                    $qb->setParameter($valuePlaceholder, $value);
                }
            }
        }

        // Is not equal
        if (isset($query['value']['nequal']) && is_array($query['value']['nequal'])) {
            foreach ($query['value']['nequal'] as $propertyId => $values) {
                if (!is_array($values)) {
                    continue;
                }
                foreach ($values as $value) {
                    $valuesAlias = $this->getToken();
                    $valuePlaceholder = $this->getToken();
                    $qb->leftJoin(
                        $this->getEntityClass() . '.values', $valuesAlias, 'WITH',
                        $qb->expr()->andX(
                            $qb->expr()->eq("$valuesAlias.value", ":$valuePlaceholder"),
                            $qb->expr()->eq("$valuesAlias.property", (int) $propertyId)
                        )
                    );
                    $qb->andWhere($qb->expr()->isNull(
                        "$valuesAlias.value"
                    ));
                    $qb->setParameter($valuePlaceholder, $value);
                }
            }
        }

        // Contains
        if (isset($query['value']['contain']) && is_array($query['value']['contain'])) {
            foreach ($query['value']['contain'] as $propertyId => $values) {
                if (!is_array($values)) {
                    continue;
                }
                foreach ($values as $value) {
                    $valuesAlias = $this->getToken();
                    $valuePlaceholder = $this->getToken();
                    $qb->innerJoin(
                        $this->getEntityClass() . '.values', $valuesAlias, 'WITH',
                        $qb->expr()->eq(
                            "$valuesAlias.property", (int) $propertyId
                        )
                    );
                    $qb->andWhere($qb->expr()->like(
                        "$valuesAlias.value", ":$valuePlaceholder"
                    ));
                    $qb->setParameter($valuePlaceholder, "%$value%");
                }
            }
        }

        // Does not contain
        if (isset($query['value']['ncontain']) && is_array($query['value']['ncontain'])) {
            foreach ($query['value']['ncontain'] as $propertyId => $values) {
                if (!is_array($values)) {
                    continue;
                }
                foreach ($values as $value) {
                    $valuesAlias = $this->getToken();
                    $valuePlaceholder = $this->getToken();
                    $qb->leftJoin(
                        $this->getEntityClass() . '.values', $valuesAlias, 'WITH',
                        $qb->expr()->andX(
                            $qb->expr()->like("$valuesAlias.value", ":$valuePlaceholder"),
                            $qb->expr()->eq("$valuesAlias.property", (int) $propertyId)
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
