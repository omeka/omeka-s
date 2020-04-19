<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddAssignNewItems implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->exec("ALTER TABLE site CHANGE assign_on_create assign_new_items TINYINT(1) DEFAULT '0' NOT NULL");
    }
}
