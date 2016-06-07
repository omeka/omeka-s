<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class RemoveUsername implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('DROP INDEX UNIQ_8D93D649F85E0677 ON user;');
        $conn->query('ALTER TABLE user DROP username;');
    }
}
