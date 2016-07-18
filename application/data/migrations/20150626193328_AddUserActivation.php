<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddUserActivation implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('CREATE TABLE user_activation (id VARCHAR(32) NOT NULL, user_id INT DEFAULT NULL, created DATETIME NOT NULL, UNIQUE INDEX UNIQ_BB0FA69BA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;');
        $conn->query('ALTER TABLE user_activation ADD CONSTRAINT FK_BB0FA69BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id);');
    }
}
