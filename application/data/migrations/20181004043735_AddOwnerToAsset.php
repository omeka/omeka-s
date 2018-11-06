<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddOwnerToAsset implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->exec('ALTER TABLE asset ADD owner_id INT DEFAULT NULL;');
        $conn->exec('ALTER TABLE asset ADD CONSTRAINT FK_2AF5A5C7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id) ON DELETE SET NULL;');
        $conn->exec('CREATE INDEX IDX_2AF5A5C7E3C61F9 ON asset (owner_id);');
    }
}
