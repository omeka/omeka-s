<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class RemoveFile implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('ALTER TABLE media DROP FOREIGN KEY FK_6A2CA10C93CB796C, DROP file_id, ADD filename VARCHAR(255) DEFAULT NULL');
        $conn->query('DROP TABLE file');
    }
}
