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

        $valuesAlias  = $this->getToken();
        $qb->innerJoin(
            $this->getEntityClass() . '.values', $valuesAlias
        );

        if (isset($query['value']['equal']) && is_array($query['value']['equal'])) {
            foreach ($query['value']['equal'] as $term => $values) {
                if (!is_array($values) || !$this->isTerm($term)) {
                    continue;
                }
                foreach ($values as $value) {
                    $propertyAlias         = $this->getToken();
                    $vocabularyAlias       = $this->getToken();
                    $valuePlaceholder      = $this->getToken();
                    $vocabularyPlaceholder = $this->getToken();
                    $propertyPlaceholder   = $this->getToken();

                    $qb->innerJoin(
                        "$valuesAlias.property",
                        $propertyAlias,
                        'WITH',
                        $qb->expr()->eq(
                            "$propertyAlias.localName", ":$propertyPlaceholder"
                        )
                    );
                    $qb->innerJoin(
                        "$propertyAlias.vocabulary",
                        $vocabularyAlias,
                        'WITH',
                        $qb->expr()->eq(
                            "$vocabularyAlias.prefix", ":$vocabularyPlaceholder"
                        )
                    );
                    $qb->andWhere($qb->expr()->eq(
                        "$valuesAlias.value", ":$valuePlaceholder"
                    ));

                    list($prefix, $localName) = explode(':', $term);
                    $qb->setParameters(array(
                        $vocabularyPlaceholder => $prefix,
                        $propertyPlaceholder   => $localName,
                        $valuePlaceholder      => $value
                    ));
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
