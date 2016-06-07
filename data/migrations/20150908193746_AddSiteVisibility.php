<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddSiteVisibility implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->exec('ALTER TABLE site ADD is_public TINYINT(1) NOT NULL;');
    }
}
