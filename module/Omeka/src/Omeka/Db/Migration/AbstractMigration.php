<?php
namespace Omeka\Db\Migration;

use Doctrine\DBAL\Connection;

/**
 * Abstract migration class.
 *
 * Most migrations should extend from this class.
 */
abstract class AbstractMigration implements MigrationInterface
{
    /**
     * Default downgrade.
     *
     * By default, downgrade is unsupported and simply throws an exception.
     *
     * @throws Omeka\Db\Migration\Exception\DowngradeUnsupportedException
     */
    public function down(Connection $conn, TableResolver $resolver)
    {
        throw new Exception\DowngradeUnsupportedException('This migration cannot be downgraded.');
    }
}
