<?php
namespace Omeka\Db\Migration;

use Doctrine\DBAL\Connection;

/**
 * Interface for database migrations.
 */
interface MigrationInterface
{
    /**
     * Upgrade the database.
     */
    public function up(Connection $conn);
}
