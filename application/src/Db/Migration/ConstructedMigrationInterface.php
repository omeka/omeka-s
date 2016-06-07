<?php
namespace Omeka\Db\Migration;

use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Interface for database migrations.
 */
interface ConstructedMigrationInterface extends MigrationInterface
{
    /**
     * Return an instance of this migration.
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public static function create(ServiceLocatorInterface $serviceLocator);
}
