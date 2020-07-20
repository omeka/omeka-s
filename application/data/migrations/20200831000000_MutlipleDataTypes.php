<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class MutlipleDataTypes implements MigrationInterface
{
    public function up(Connection $conn)
    {
        // Remove previous indexes if any.
        $indexes = [
            'UNIQ_4689E2F116131EA549213EC',
            'UNIQ_4689E2F116131EA549213ECA633250B',
            'UNIQ_4689E2F116131EA549213EC37919CCB',
            'IDX_4689E2F116131EA549213EC',
        ];
        foreach ($indexes as $index) {
            $sql = <<<SQL
SHOW INDEX FROM `resource_template_property` WHERE Key_name = "$index";
SQL;
            if ($conn->fetchAll($sql)) {
                $sql = <<<SQL
ALTER TABLE `resource_template_property` DROP INDEX $index;
SQL;
                $conn->exec($sql);
            }
        }

        $sql = <<<SQL
ALTER TABLE `resource_template_property` CHANGE `data_type` `data_type` LONGTEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '(DC2Type:json_array)';
SQL;
        $conn->exec($sql);

        $sql = <<<SQL
CREATE INDEX IDX_4689E2F116131EA549213EC ON resource_template_property (resource_template_id, property_id);
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
SET `data_type` = CONCAT('["', REPLACE(TRIM(`data_type`), "\n", '","'), '"]')
WHERE `data_type` IS NOT NULL AND SUBSTRING(`data_type`, 1, 1) != "[";
SQL;
        $conn->exec($sql);
    }
}
