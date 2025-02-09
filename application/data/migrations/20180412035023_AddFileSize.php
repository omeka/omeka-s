<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddFileSize implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->exec('ALTER TABLE media ADD size BIGINT DEFAULT NULL;');
    }
}
