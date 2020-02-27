<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddHasAllItems implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->exec("ALTER TABLE site ADD has_all_items TINYINT(1) DEFAULT '0' NOT NULL;");
    }
}
