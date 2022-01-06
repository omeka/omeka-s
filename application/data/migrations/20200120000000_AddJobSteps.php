<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddJobSteps implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->exec('ALTER TABLE job ADD step INT DEFAULT 0 AFTER args, ADD total_steps INT DEFAULT 0 AFTER step;');
    }
}
