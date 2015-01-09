<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class ResourceTemplateLabelUniqueIndex extends AbstractMigration
{
    public function up()
    {
        $this->getConnection()->query('CREATE UNIQUE INDEX UNIQ_39ECD52EEA750E8 ON resource_template (`label`);');
    }
}
