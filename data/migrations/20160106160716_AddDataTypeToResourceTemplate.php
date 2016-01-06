<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class AddDataTypeToResourceTemplate extends AbstractMigration
{
    public function up()
    {
        $connection = $this->getConnection();
        $connection->query('ALTER TABLE resource_template_property ADD data_type VARCHAR(255) NOT NULL;');
        $connection->query('UPDATE resource_template_property SET data_type = "normal";');
    }
}
