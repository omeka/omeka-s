<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddUserSetting implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->exec('CREATE TABLE user_setting (id VARCHAR(190) NOT NULL, user_id INT NOT NULL, value LONGTEXT NOT NULL COMMENT \'(DC2Type:json_array)\', INDEX IDX_C779A692A76ED395 (user_id), PRIMARY KEY(id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;');
        $conn->exec('ALTER TABLE user_setting ADD CONSTRAINT FK_C779A692A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;');
    }
}
