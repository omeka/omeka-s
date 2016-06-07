<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class UpdateRenderers implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('UPDATE media SET renderer = ingester');
        $conn->query("UPDATE media SET renderer = 'file' WHERE renderer IN ('url','upload')");
    }
}
