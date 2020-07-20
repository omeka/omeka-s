<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class MutlipleDataTypes implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $sql = <<<SQL
ALTER TABLE `resource_template_property` CHANGE `data_type` `data_type` LONGTEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '(DC2Type:json_array)';
SQL;
        $conn->exec($sql);

        $sql = <<<SQL
UPDATE `resource_template_property`
SET `data_type` = NULL
WHERE `data_type` IS NULL OR TRIM(`data_type`) = "";
SQL;
        $conn->exec($sql);

        $sql = <<<SQL
UPDATE `resource_template_property`
SET `data_type` = CONCAT('["', TRIM(`data_type`), '"]')
WHERE `data_type` IS NOT NULL;
SQL;
        $conn->exec($sql);
    }
}
