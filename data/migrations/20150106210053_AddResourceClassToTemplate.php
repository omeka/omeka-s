<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class AddResourceClassToTemplate extends AbstractMigration
{
    public function up()
    {
        $connection = $this->getConnection();
        $connection->query('ALTER TABLE resource_template ADD resource_class_id INT DEFAULT NULL');
        $connection->query('ALTER TABLE resource_template ADD CONSTRAINT FK_39ECD52E448CC1BD FOREIGN KEY (resource_class_id) REFERENCES resource_class (id)');
        $connection->query('CREATE INDEX IDX_39ECD52E448CC1BD ON resource_template (resource_class_id)');
    }
}
