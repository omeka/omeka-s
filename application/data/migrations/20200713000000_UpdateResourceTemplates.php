<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class UpdateResourceTemplates implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $sql = <<<SQL
ALTER TABLE resource_template_property CHANGE data_type data_type VARCHAR(766) DEFAULT NULL;
SQL;
        $conn->exec($sql);

        $sql = <<<SQL
ALTER TABLE resource_template_property DROP INDEX UNIQ_4689E2F116131EA549213EC;
SQL;
        $conn->exec($sql);
        $sql = <<<SQL
CREATE UNIQUE INDEX UNIQ_4689E2F116131EA549213ECA633250B ON resource_template_property (resource_template_id, property_id, alternate_label);
SQL;
        $conn->exec($sql);
        $sql = <<<SQL
CREATE UNIQUE INDEX UNIQ_4689E2F116131EA549213EC37919CCB ON resource_template_property (resource_template_id, property_id, data_type);
SQL;
        $conn->exec($sql);

        $sql = <<<SQL
INSERT INTO setting (id, value) VALUES ('resource_default_datatypes', '["literal","resource","uri"]') ON DUPLICATE KEY UPDATE value=value;
SQL;
        $conn->exec($sql);
    }
}
