<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class AddResourceTemplatePropertyPosition extends AbstractMigration
{
    public function up()
    {
        $connection = $this->getConnection();
        $connection->query('ALTER TABLE resource_template_property ADD position INT DEFAULT NULL;');
    }
}
