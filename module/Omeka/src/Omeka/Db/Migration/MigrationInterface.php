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
     *
     * @param Doctrine\DBAL\Connection $conn Database connection
     * @param Omeka\Db\Migration\TableResolver $resolver Table resolver
     */
    public function up(Connection $conn, TableResolver $resolver);

    /**
     * Downgrade the database.
     *
     * @param Doctrine\DBAL\Connection $conn Database connection
     * @param Omeka\Db\Migration\TableResolver $resolver Table resolver
     */
    public function down(Connection $conn, TableResolver $resolver);
}
