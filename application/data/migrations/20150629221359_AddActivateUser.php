<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddActivateUser implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('ALTER TABLE user_activation ADD activate TINYINT(1) NOT NULL;');
    }
}
