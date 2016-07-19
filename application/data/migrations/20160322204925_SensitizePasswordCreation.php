<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class SensitizePasswordCreation implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('ALTER TABLE password_creation CHANGE id id VARCHAR(32) NOT NULL COLLATE utf8mb4_bin');
    }
}
