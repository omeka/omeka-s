<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class UpdateResourceTemplates implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $sql = <<<SQL
ALTER TABLE `resource_template_property` CHANGE `data_type` `data_type` VARCHAR(766) DEFAULT NULL;
SQL;
        $conn->exec($sql);

        // Remove previous indexes if any.
        $indexes = [
            'UNIQ_4689E2F116131EA549213EC',
            'UNIQ_4689E2F116131EA549213ECA633250B',
            'UNIQ_4689E2F116131EA549213EC37919CCB',
            'IDX_4689E2F116131EA549213EC',
        ];
        foreach ($indexes as $index) {
            $sql = <<<SQL
SHOW INDEX FROM `resource_template_property` WHERE Key_name = '$index';
SQL;
            if ($conn->fetchAll($sql)) {
                $sql = <<<SQL
ALTER TABLE `resource_template_property` DROP INDEX $index;
SQL;
                $conn->exec($sql);
            }
        }

        $sql = <<<SQL
CREATE INDEX IDX_4689E2F116131EA549213EC ON `resource_template_property` (`resource_template_id`, `property_id`);
SQL;
        $conn->exec($sql);

        $sql = <<<SQL
INSERT INTO `setting` (`id`, `value`)
VALUES ('resource_default_datatypes', '["literal","resource","uri"]') ON DUPLICATE KEY UPDATE `value`=`value`;
SQL;
        $conn->exec($sql);
    }
}
