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
     */
    public function getTableName($entityName)
    {
        return $this->em->getClassMetadata($entityName)->getTableName();
    }
}
