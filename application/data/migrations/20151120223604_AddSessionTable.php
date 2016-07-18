<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddSessionTable implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('CREATE TABLE session (id VARCHAR(190) NOT NULL, modified INT NOT NULL, data LONGBLOB NOT NULL, PRIMARY KEY(id, modified)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;');
    }
}
