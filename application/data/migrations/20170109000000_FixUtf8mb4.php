<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class FixUtf8mb4 implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query("ALTER TABLE item_item_set CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }
}
