<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class UpdateItemSetManyToManyCharset implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->exec('ALTER TABLE `item_item_set` DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }
}
