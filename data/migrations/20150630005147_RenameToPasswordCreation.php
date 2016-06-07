<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class RenameToPasswordCreation implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('CREATE TABLE password_creation (id VARCHAR(32) NOT NULL, user_id INT NOT NULL, created DATETIME NOT NULL, activate TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_C77917B4A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;');
        $conn->query('ALTER TABLE password_creation ADD CONSTRAINT FK_C77917B4A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;');
        $conn->query('DROP TABLE user_activation');
    }
}
