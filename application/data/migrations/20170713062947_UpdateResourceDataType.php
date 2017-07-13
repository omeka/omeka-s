<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class UpdateResourceDataType implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('UPDATE value SET type = "resource:all" WHERE type = "resource"');
    }
}
