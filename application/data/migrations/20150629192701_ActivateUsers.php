<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class ActivateUsers implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query("UPDATE user SET is_active = '1'");
    }
}
