<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddCascadeDeleteProperty implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('ALTER TABLE resource_template_property DROP FOREIGN KEY FK_4689E2F1549213EC;');
        $conn->query('ALTER TABLE resource_template_property ADD CONSTRAINT FK_4689E2F1549213EC FOREIGN KEY (property_id) REFERENCES property (id) ON DELETE CASCADE;');
    }
}
