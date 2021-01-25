<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class DoctrineJsonType implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $sqls = <<<SQL
ALTER TABLE `job` CHANGE `args` `args` LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)';
ALTER TABLE `media` CHANGE `data` `data` LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)';
ALTER TABLE `resource_template_property` CHANGE `data_type` `data_type` LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)';
ALTER TABLE `setting` CHANGE `value` `value` LONGTEXT NOT NULL COMMENT '(DC2Type:json)';
ALTER TABLE `site` CHANGE `navigation` `navigation` LONGTEXT NOT NULL COMMENT '(DC2Type:json)', CHANGE `item_pool` `item_pool` LONGTEXT NOT NULL COMMENT '(DC2Type:json)';
ALTER TABLE `site_page_block` CHANGE `data` `data` LONGTEXT NOT NULL COMMENT '(DC2Type:json)';
ALTER TABLE `site_setting` CHANGE `value` `value` LONGTEXT NOT NULL COMMENT '(DC2Type:json)';
ALTER TABLE `user_setting` CHANGE `value` `value` LONGTEXT NOT NULL COMMENT '(DC2Type:json)';

SQL;
        foreach (explode(";\n", $sqls) as $sql) {
            $conn->exec($sql);
        }
    }
}
