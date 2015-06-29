<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class DeleteActivationOnUserDelete extends AbstractMigration
{
    public function up()
    {
        $connection = $this->getConnection();
        $connection->query('ALTER TABLE user_activation DROP FOREIGN KEY FK_BB0FA69BA76ED395;');
        $connection->query('ALTER TABLE user_activation CHANGE user_id user_id INT NOT NULL;');
        $connection->query('ALTER TABLE user_activation ADD CONSTRAINT FK_BB0FA69BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;');
    }
}
