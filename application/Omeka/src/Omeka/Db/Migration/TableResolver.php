<?php
namespace Omeka\Db\Migration;

use Doctrine\ORM\EntityManager;

/**
 * Encapsulates the mapping from entities to table names.
 *
 * Used by the migrations to get a table name to use in SQL.
 */
class TableResolver
{
    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * Create the resolver.
     *
     * @param Doctrine\ORM\EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Get the table name for the given entity.
     *
     * @param string $entityName Name of the Entity
     * @return string Name of the underlying SQL table.
     */
    public function getTableName($entityName)
    {
        return $this->em->getClassMetadata($entityName)->getTableName();
    }

    /**
     * Get the column name for the given field of an entity.
     *
     * @param string $entityName Name of the Entity
     * @param string $fieldName Name of the field
     * @return string Name of the underlying SQL column.
     */
    public function getColumnName($entityName, $fieldName)
    {
        return $this->em->getClassMetadata($entityName)->getColumnName($fieldName);
    }
}
