<?php
namespace NumericDataTypes\DataType;

use Doctrine\ORM\QueryBuilder;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Adapter\AdapterInterface;
use Omeka\DataType\DataTypeWithOptionsInterface;
use Omeka\Api\Representation\ValueRepresentation;

abstract class AbstractDataType implements DataTypeWithOptionsInterface, DataTypeInterface
{
    public function getOptgroupLabel()
    {
        return 'Numeric'; // @translate
    }

    public function prepareForm(PhpRenderer $view)
    {
    }

    public function toString(ValueRepresentation $value)
    {
        return (string) $value->value();
    }

    public function getFulltextText(PhpRenderer $view, ValueRepresentation $value)
    {
        return $value->value();
    }

    public function buildQuery(AdapterInterface $adapter, QueryBuilder $qb, array $query)
    {
    }

    public function sortQuery(AdapterInterface $adapter, QueryBuilder $qb, array $query, $type, $propertyId)
    {
    }

    /**
     * Add a less-than query.
     *
     * Use in self::buildQuery() to perform simple < comparisons.
     *
     * @param AdapterInterface $adapter
     * @param QueryBuilder $qb
     * @param int|null propertyId
     * @param int $number
     */
    public function addLessThanQuery(AdapterInterface $adapter, QueryBuilder $qb, $propertyId, $number)
    {
        $alias = $adapter->createAlias();
        $with = $qb->expr()->eq("$alias.resource", 'omeka_root.id');
        if (is_numeric($propertyId)) {
            $with = $qb->expr()->andX(
                $qb->expr()->eq("$alias.resource", 'omeka_root.id'),
                $qb->expr()->eq("$alias.property", (int) $propertyId)
            );
        }
        $qb->leftJoin($this->getEntityClass(), $alias, 'WITH', $with);
        $qb->andWhere($qb->expr()->lt(
            "$alias.value",
            $adapter->createNamedParameter($qb, $number)
        ));
    }

    /**
     * Add a greater-than query.
     *
     * Use in self::buildQuery() to perform simple > comparisons.
     *
     * @param AdapterInterface $adapter
     * @param QueryBuilder $qb
     * @param int|null propertyId
     * @param int $number
     */
    public function addGreaterThanQuery(AdapterInterface $adapter, QueryBuilder $qb, $propertyId, $number)
    {
        $alias = $adapter->createAlias();
        $with = $qb->expr()->eq("$alias.resource", 'omeka_root.id');
        if (is_numeric($propertyId)) {
            $with = $qb->expr()->andX(
                $with,
                $qb->expr()->eq("$alias.property", (int) $propertyId)
            );
        }
        $qb->leftJoin($this->getEntityClass(), $alias, 'WITH', $with);
        $qb->andWhere($qb->expr()->gt(
            "$alias.value",
            $adapter->createNamedParameter($qb, $number)
        ));
    }

    /**
     * Add a less-than-or-equal-to query.
     *
     * Use in self::buildQuery() to perform simple <= comparisons.
     *
     * @param AdapterInterface $adapter
     * @param QueryBuilder $qb
     * @param int|null propertyId
     * @param int $number
     */
    public function addLessThanOrEqualToQuery(AdapterInterface $adapter, QueryBuilder $qb, $propertyId, $number)
    {
        $alias = $adapter->createAlias();
        $with = $qb->expr()->eq("$alias.resource", 'omeka_root.id');
        if (is_numeric($propertyId)) {
            $with = $qb->expr()->andX(
                $with,
                $qb->expr()->eq("$alias.property", (int) $propertyId)
            );
        }
        $qb->leftJoin($this->getEntityClass(), $alias, 'WITH', $with);
        $qb->andWhere($qb->expr()->lte(
            "$alias.value",
            $adapter->createNamedParameter($qb, $number)
        ));
    }

    /**
     * Add a greater-than-or-equal-to query.
     *
     * Use in self::buildQuery() to perform simple >= comparisons.
     *
     * @param AdapterInterface $adapter
     * @param QueryBuilder $qb
     * @param int|null propertyId
     * @param int $number
     */
    public function addGreaterThanOrEqualToQuery(AdapterInterface $adapter, QueryBuilder $qb, $propertyId, $number)
    {
        $alias = $adapter->createAlias();
        $with = $qb->expr()->eq("$alias.resource", 'omeka_root.id');
        if (is_numeric($propertyId)) {
            $with = $qb->expr()->andX(
                $with,
                $qb->expr()->eq("$alias.property", (int) $propertyId)
            );
        }
        $qb->leftJoin($this->getEntityClass(), $alias, 'WITH', $with);
        $qb->andWhere($qb->expr()->gte(
            "$alias.value",
            $adapter->createNamedParameter($qb, $number)
        ));
    }
}
