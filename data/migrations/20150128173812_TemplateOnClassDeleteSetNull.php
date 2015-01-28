<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class TemplateOnClassDeleteSetNull extends AbstractMigration
{
    public function up()
    {
        $connection = $this->getConnection();
        $connection->query('ALTER TABLE resource_template DROP FOREIGN KEY FK_39ECD52E448CC1BD;');
        $connection->query('ALTER TABLE resource_template ADD CONSTRAINT FK_39ECD52E448CC1BD FOREIGN KEY (resource_class_id) REFERENCES resource_class (id) ON DELETE SET NULL;');
    }
}
