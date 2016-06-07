<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class TemplateOnClassDeleteSetNull implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('ALTER TABLE resource_template DROP FOREIGN KEY FK_39ECD52E448CC1BD;');
        $conn->query('ALTER TABLE resource_template ADD CONSTRAINT FK_39ECD52E448CC1BD FOREIGN KEY (resource_class_id) REFERENCES resource_class (id) ON DELETE SET NULL;');
    }
}
