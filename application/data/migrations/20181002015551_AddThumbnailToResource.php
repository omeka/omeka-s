<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddThumbnailToResource implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->exec('ALTER TABLE resource ADD thumbnail_id INT DEFAULT NULL;');
        $conn->exec('ALTER TABLE resource ADD CONSTRAINT FK_BC91F416FDFF2E92 FOREIGN KEY (thumbnail_id) REFERENCES asset (id) ON DELETE SET NULL;');
        $conn->exec('CREATE INDEX IDX_BC91F416FDFF2E92 ON resource (thumbnail_id);');
    }
}
