<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class DropSiteItems implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->exec('DROP TABLE site_item');
    }
}
