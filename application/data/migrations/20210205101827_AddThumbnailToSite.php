<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddThumbnailToSite implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->exec("ALTER TABLE site ADD thumbnail_id INT DEFAULT NULL;");
        $conn->exec("ALTER TABLE site ADD CONSTRAINT FK_694309E4FDFF2E92 FOREIGN KEY (thumbnail_id) REFERENCES asset (id) ON DELETE SET NULL;");
        $conn->exec("CREATE INDEX IDX_694309E4FDFF2E92 ON site (thumbnail_id);");
    }
}
