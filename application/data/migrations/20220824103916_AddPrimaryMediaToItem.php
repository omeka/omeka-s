<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddPrimaryMediaToItem implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('ALTER TABLE item ADD primary_media_id INT DEFAULT NULL;');
        $conn->query('ALTER TABLE item ADD CONSTRAINT FK_1F1B251ECBE0B084 FOREIGN KEY (primary_media_id) REFERENCES media (id) ON DELETE SET NULL;');
        $conn->query('CREATE INDEX IDX_1F1B251ECBE0B084 ON item (primary_media_id);');
    }
}
