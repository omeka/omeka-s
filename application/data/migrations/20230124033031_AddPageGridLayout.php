<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddPageGridLayout implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query("ALTER TABLE site_page ADD layout VARCHAR(255) DEFAULT NULL, ADD layout_data LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)';");
        $conn->query("ALTER TABLE site_page_block ADD layout_data LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)';");
    }
}
