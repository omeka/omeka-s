<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class OpenItemSets implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('ALTER TABLE item_set ADD is_open TINYINT(1) NOT NULL;');
    }
}
