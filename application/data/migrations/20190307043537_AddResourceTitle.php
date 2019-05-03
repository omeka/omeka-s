<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddResourceTitle implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->exec('ALTER TABLE resource ADD title LONGTEXT DEFAULT NULL;');
        $conn->exec('ALTER TABLE resource_template ADD title_property_id INT DEFAULT NULL;');
        $conn->exec('ALTER TABLE resource_template ADD CONSTRAINT FK_39ECD52E724734A3 FOREIGN KEY (title_property_id) REFERENCES property (id) ON DELETE SET NULL;');
        $conn->exec('CREATE INDEX IDX_39ECD52E724734A3 ON resource_template (title_property_id);');
    }
}
