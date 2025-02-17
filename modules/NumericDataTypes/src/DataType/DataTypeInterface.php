<?php
namespace NumericDataTypes\DataType;

use Doctrine\ORM\QueryBuilder;
use NumericDataTypes\Entity\NumericDataTypesNumber;
use Omeka\Api\Adapter\AdapterInterface;
use Omeka\Entity\Value;

interface DataTypeInterface
{
    /**
     * Get the fully qualified name of the corresponding entity.
     *
     * @return string
     */
    public function getEntityClass();

    /**
     * Set the number value(s) to a number entity.
     *
     * @param NumericDataTypesNumber $entity
     * @param Value $value
     */
    public function setEntityValues(NumericDataTypesNumber $entity, Value $value);

    /**
     * Build a numeric query.
     *
     * @param AdapterInterface $adapter
     * @param QueryBuilder $qb
     * @param array $query
     */
    public function buildQuery(AdapterInterface $adapter, QueryBuilder $qb, array $query);

    /**
     * Sort a numeric query.
     *
     * @param AdapterInterface $adapter
     * @param QueryBuilder $qb
     * @param array $query
     * @param string $type
     * @param int $propertyId
     */
    public function sortQuery(AdapterInterface $adapter, QueryBuilder $qb, array $query, $type, $propertyId);
}
