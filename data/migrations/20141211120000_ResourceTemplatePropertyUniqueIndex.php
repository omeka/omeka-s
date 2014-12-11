<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class ResourceTemplatePropertyUniqueIndex extends AbstractMigration
{
    public function up()
    {
        $this->getConnection()->query('CREATE UNIQUE INDEX UNIQ_4689E2F116131EA549213EC ON resource_template_property (resource_template_id, property_id);');
    }
}
