<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddUserIsActive implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('ALTER TABLE user ADD is_active TINYINT(1) NOT NULL;');
    }
}
