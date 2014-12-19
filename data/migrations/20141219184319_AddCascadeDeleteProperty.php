<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class AddCascadeDeleteProperty extends AbstractMigration
{
    public function up()
    {
        $connection = $this->getConnection();
        $connection->query('ALTER TABLE resource_template_property DROP FOREIGN KEY FK_4689E2F1549213EC;');
        $connection->query('ALTER TABLE resource_template_property ADD CONSTRAINT FK_4689E2F1549213EC FOREIGN KEY (property_id) REFERENCES property (id) ON DELETE CASCADE;');
    }
}
