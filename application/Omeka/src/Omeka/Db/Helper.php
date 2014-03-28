<?php
namespace Omeka\Db;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class Helper implements ServiceLocatorAwareInterface
{
    const TABLE_PREFIX_PLACEHOLDER = 'OMEKA_TABLE_PREFIX_';

    /**
     * @var string
     */
    protected $tablePrefix;

    /**
     * @var Connection
     */
    protected $connection;

   /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var ServiceLocatorInterface
     */
    protected $services;

    /**
     * Execute SQL statements.
     *
     * All statements should stand on their own. Do not parametrize them or
     * interpolate user generated data. For those purposes, use executeQuery()
     * in the Omeka\Connection service.
     *
     * Statements passed as a string will be exploded by semicolon and executed
     * one at a time. Do not pass a string if your statements contain a
     * semicolon that do not indicate an end of a SQL statement.
     *
     * This will replace the string set by self::TABLE_PREFIX_PLACEHOLDER with
     * the configured table prefix. Make sure your statements do not contain the
     * placeholder string if it does not indicate a replacement.
     *
     * @param string|array $statements
     */
    public function execute($statements)
    {
        if (is_string($statements)) {
            $this->execute(explode(';', $statements));
        }
        if (!is_array($statements)) {
            return;
        }
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if ('' === $statement) {
                continue;
            }
            $statement = str_replace(
                self::TABLE_PREFIX_PLACEHOLDER,
                $this->getTablePrefix(),
                $statement
            );
            $this->getConnection()->exec($statement);
        }
    }

    /**
     * Get the table name for the given entity.
     *
     * @param string $entityName Name of the Entity
     * @return string Name of the underlying SQL table.
     */
    public function getTableName($entityName)
    {
        return $this->getEntityManager()
            ->getClassMetadata($entityName)->getTableName();
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
        return $this->getEntityManager()
            ->getClassMetadata($entityName)->getColumnName($fieldName);
    }

    /**
     * Get the configured table prefix.
     *
     * @return string
     */
    public function getTablePrefix()
    {
        if (null === $this->tablePrefix) {
            $appConfig = $this->getServiceLocator()->get('ApplicationConfig');
            $this->tablePrefix = $appConfig['connection']['table_prefix'];
        }
        return $this->tablePrefix;
    }

    /**
     * Get the entity manager
     *
     * @return EntityManager
     */
    public function getEntityManager()
    {
        if (null === $this->entityManager) {
            $this->entityManager = $this->getServiceLocator()
                ->get('Omeka\EntityManager');
        }
        return $this->entityManager;
    }

    /**
     * Get the db connection
     *
     * @return Connection
     */
    public function getConnection()
    {
        if (null === $this->connection) {
            $this->connection = $this->getServiceLocator()
                ->get('Omeka\Connection');
        }
        return $this->connection;
    }

     /**
     * {@inheritDoc}
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->services = $serviceLocator;
    }

    /**
     * {@inheritDoc}
     */
    public function getServiceLocator()
    {
        return $this->services;
    }
}
