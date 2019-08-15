<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddFulltextSearch implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->exec('CREATE TABLE fulltext_search (id INT NOT NULL, resource VARCHAR(190) NOT NULL, title LONGTEXT DEFAULT NULL, text LONGTEXT DEFAULT NULL, FULLTEXT INDEX IDX_AA31FE4A2B36786B3B8BA7C7 (title, text), PRIMARY KEY(id, resource)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;');
    }
}
