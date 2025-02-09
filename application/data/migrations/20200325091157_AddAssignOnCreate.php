<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddAssignOnCreate implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->exec("ALTER TABLE site CHANGE has_all_items assign_on_create TINYINT(1) DEFAULT '0' NOT NULL");
    }
}
