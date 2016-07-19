<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddJobEntity implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('CREATE TABLE job (id INT AUTO_INCREMENT NOT NULL, owner_id INT DEFAULT NULL, pid VARCHAR(255) DEFAULT NULL, status VARCHAR(255) DEFAULT NULL, class VARCHAR(255) NOT NULL, args LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json_array)\', started DATETIME NOT NULL, stopped DATETIME DEFAULT NULL, INDEX IDX_FBD8E0F87E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;');
        $conn->query('ALTER TABLE job ADD CONSTRAINT FK_FBD8E0F87E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id) ON DELETE SET NULL;');
    }
}
