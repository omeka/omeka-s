<?php
namespace Omeka\Db\Migration;

use Zend\ServiceManager\ServiceLocatorAwareInterface;

/**
 * Interface for database migrations.
 */
interface MigrationInterface extends ServiceLocatorAwareInterface
{
    /**
     * Upgrade the database.
     */
    public function up();

    /**
     * Downgrade the database.
     */
    public function down();
}
