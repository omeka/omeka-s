<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddOnDelete implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $connection->query('ALTER TABLE vocabulary DROP FOREIGN KEY FK_9099C97B7E3C61F9;');
        $connection->query('ALTER TABLE vocabulary ADD CONSTRAINT FK_9099C97B7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id) ON DELETE SET NULL;');
        $connection->query('ALTER TABLE resource_class DROP FOREIGN KEY FK_C6F063AD7E3C61F9;');
        $connection->query('ALTER TABLE resource_class ADD CONSTRAINT FK_C6F063AD7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id) ON DELETE SET NULL;');
        $connection->query('ALTER TABLE property DROP FOREIGN KEY FK_8BF21CDE7E3C61F9;');
        $connection->query('ALTER TABLE property ADD CONSTRAINT FK_8BF21CDE7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id) ON DELETE SET NULL;');
        $connection->query('ALTER TABLE resource_template DROP FOREIGN KEY FK_39ECD52E7E3C61F9;');
        $connection->query('ALTER TABLE resource_template ADD CONSTRAINT FK_39ECD52E7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id) ON DELETE SET NULL;');
        $connection->query('ALTER TABLE resource DROP FOREIGN KEY FK_BC91F41616131EA;');
        $connection->query('ALTER TABLE resource DROP FOREIGN KEY FK_BC91F416448CC1BD;');
        $connection->query('ALTER TABLE resource DROP FOREIGN KEY FK_BC91F4167E3C61F9;');
        $connection->query('ALTER TABLE resource ADD CONSTRAINT FK_BC91F41616131EA FOREIGN KEY (resource_template_id) REFERENCES resource_template (id) ON DELETE SET NULL;');
        $connection->query('ALTER TABLE resource ADD CONSTRAINT FK_BC91F416448CC1BD FOREIGN KEY (resource_class_id) REFERENCES resource_class (id) ON DELETE SET NULL;');
        $connection->query('ALTER TABLE resource ADD CONSTRAINT FK_BC91F4167E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id) ON DELETE SET NULL;');
    }
}
