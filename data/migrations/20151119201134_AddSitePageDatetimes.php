<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddSitePageDatetimes implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('ALTER TABLE site_page ADD created DATETIME NOT NULL, ADD modified DATETIME DEFAULT NULL;');
    }
}
