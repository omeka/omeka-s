<?php declare(strict_types=1);

namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddIndexFullTextIsPublic implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $sql = <<<'SQL'
ALTER TABLE `fulltext_search` ADD INDEX `is_public` (`is_public`);
SQL;
        try {
            $conn->executeStatement($sql);
        } catch (\Exception $e) {
            // Index exists.
        }
    }
}
