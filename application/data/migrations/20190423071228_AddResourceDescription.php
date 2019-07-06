<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddResourceDescription implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->exec('ALTER TABLE resource_template ADD description_property_id INT DEFAULT NULL;');
        $conn->exec('ALTER TABLE resource_template ADD CONSTRAINT FK_39ECD52EB84E0D1D FOREIGN KEY (description_property_id) REFERENCES property (id) ON DELETE SET NULL;');
        $conn->exec('CREATE INDEX IDX_39ECD52EB84E0D1D ON resource_template (description_property_id);');
    }
}
