<?php
namespace Omeka\Api\Adapter\Entity;

use Doctrine\ORM\QueryBuilder;

abstract class AbstractResourceEntityAdapter extends AbstractEntityAdapter
{
    protected function buildValueQuery(QueryBuilder $qb, array $query)
    {
        if (!isset($query['value'])) {
            return;
        }
        if (isset($query['value']['equals']) && is_array($query['value']['equals'])) {
            foreach ($query['value']['equals'] as $term => $values) {
                if (!is_array($values) || !$this->isTerm($term)) {
                    continue;
                }
                $i = 0;
                foreach ($values as $value) {
                    $valuesAlias     = "omeka_search_values_$i";
                    $propertyAlias   = "omeka_search_property_$i";
                    $vocabularyAlias = "omeka_search_vocabulary_$i";
                    $i++;

                    $qb->innerJoin($this->getEntityClass() . '.values', $valuesAlias);
                    $qb->innerJoin("$valuesAlias.property", $propertyAlias);
                    $qb->innerJoin("$propertyAlias.vocabulary", $vocabularyAlias);

                    list($prefix, $localName) = explode(':', $term);
                    $valuePlaceholder      = $this->getPlaceholder();
                    $vocabularyPlaceholder = $this->getPlaceholder();
                    $propertyPlaceholder   = $this->getPlaceholder();

                    $qb->andWhere($qb->expr()->eq(
                        "$vocabularyAlias.prefix", ":$vocabularyPlaceholder"
                    ))->setParameter($vocabularyPlaceholder, $prefix);
                    $qb->andWhere($qb->expr()->eq(
                        "$propertyAlias.localName", ":$propertyPlaceholder"
                    ))->setParameter($propertyPlaceholder, $localName);
                    $qb->andWhere($qb->expr()->eq(
                        "$valuesAlias.value", ":$valuePlaceholder"
                    ))->setParameter($valuePlaceholder, $value);
                }
            }
        }
    }

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

    protected function isTerm($term)
    {
        if (preg_match('/[a-z0-9-_]+:[a-z0-9-_]+/i', $term)) {
            return true;
        }
        return false;
    }
}
