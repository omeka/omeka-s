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
        $i = 0;
        if (isset($query['value']['equals']) && is_array($query['value']['equals'])) {
            foreach ($query['value']['equals'] as $term => $values) {
                if (!is_array($values) || !$this->isTerm($term)) {
                    continue;
                }
                foreach ($values as $value) {
                    $valuesAlias     = "omeka_search_values_$i";
                    $propertyAlias   = "omeka_search_property_$i";
                    $vocabularyAlias = "omeka_search_vocabulary_$i";

                    $valuePlaceholder      = $this->getPlaceholder();
                    $vocabularyPlaceholder = $this->getPlaceholder();
                    $propertyPlaceholder   = $this->getPlaceholder();

                    $qb->innerJoin(
                        $this->getEntityClass() . '.values', $valuesAlias
                    );
                    $qb->innerJoin(
                        "$valuesAlias.property", $propertyAlias, 'WITH',
                        $qb->expr()->eq(
                            "$propertyAlias.localName", ":$propertyPlaceholder"
                        )
                    );
                    $qb->innerJoin(
                        "$propertyAlias.vocabulary", $vocabularyAlias, 'WITH',
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

                    $i++;
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
