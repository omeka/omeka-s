<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddJobLog implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('ALTER TABLE job ADD log LONGTEXT DEFAULT NULL;');
    }
}
