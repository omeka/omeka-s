<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddResourceClassToTemplate implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('ALTER TABLE resource_template ADD resource_class_id INT DEFAULT NULL');
        $conn->query('ALTER TABLE resource_template ADD CONSTRAINT FK_39ECD52E448CC1BD FOREIGN KEY (resource_class_id) REFERENCES resource_class (id)');
        $conn->query('CREATE INDEX IDX_39ECD52E448CC1BD ON resource_template (resource_class_id)');
    }
}
