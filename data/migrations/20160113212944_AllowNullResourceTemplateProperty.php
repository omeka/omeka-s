<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class AllowNullResourceTemplateProperty extends AbstractMigration
{
    public function up()
    {
        $connection = $this->getConnection();
        $connection->query('ALTER TABLE resource_template_property CHANGE data_type data_type VARCHAR(255) DEFAULT NULL;');
        $connection->query('UPDATE resource_template_property SET data_type = NULL;');
    }
}
