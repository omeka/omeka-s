<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class addSiteSummary implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->exec('ALTER TABLE site ADD summary LONGTEXT DEFAULT NULL');
    }
}
