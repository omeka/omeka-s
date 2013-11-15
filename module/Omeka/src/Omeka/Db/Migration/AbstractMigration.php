<?php
namespace Omeka\Db\Migration;

use Doctrine\DBAL\Connection;

abstract class AbstractMigration implements MigrationInterface
{
    public function down(Connection $conn)
    {
        throw new Exception\DowngradeUnsupportedException('This migration cannot be downgraded.');
    }
}
