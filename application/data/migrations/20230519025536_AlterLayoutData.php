<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AlterLayoutData implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query("ALTER TABLE site_page_block CHANGE page_layout_data layout_data LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)';");
    }
}
