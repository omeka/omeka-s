<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class RemoveUsername extends AbstractMigration
{
    public function up()
    {
        $connection = $this->getConnection();
        $connection->query('DROP INDEX UNIQ_8D93D649F85E0677 ON user;');
        $connection->query('ALTER TABLE user DROP username;');
    }
}
