<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddAsset implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->exec('CREATE TABLE asset (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, media_type VARCHAR(255) NOT NULL, storage_id VARCHAR(190) NOT NULL, extension VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_2AF5A5C5CC5DB90 (storage_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }
}
