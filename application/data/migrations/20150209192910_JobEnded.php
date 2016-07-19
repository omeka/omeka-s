<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class JobEnded implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('ALTER TABLE job CHANGE stopped ended DATETIME DEFAULT NULL;');
    }
}
