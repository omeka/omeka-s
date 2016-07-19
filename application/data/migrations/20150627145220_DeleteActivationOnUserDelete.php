<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class DeleteActivationOnUserDelete implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('ALTER TABLE user_activation DROP FOREIGN KEY FK_BB0FA69BA76ED395;');
        $conn->query('ALTER TABLE user_activation CHANGE user_id user_id INT NOT NULL;');
        $conn->query('ALTER TABLE user_activation ADD CONSTRAINT FK_BB0FA69BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;');
    }
}
