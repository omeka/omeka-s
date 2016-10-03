<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class SeparateFileExtensions implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->exec('ALTER TABLE media ADD extension VARCHAR(255) DEFAULT NULL, CHANGE filename storage_id VARCHAR(190) DEFAULT NULL');
        $conn->exec("UPDATE media SET extension=IF(LOCATE('.', storage_id), SUBSTRING_INDEX(storage_id, '.', -1), NULL), storage_id=SUBSTRING_INDEX(storage_id, '.', 1)");
        $conn->exec('CREATE UNIQUE INDEX UNIQ_6A2CA10C5CC5DB90 ON media (storage_id)');
    }
}
