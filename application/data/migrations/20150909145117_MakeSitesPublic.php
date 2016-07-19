<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class MakeSitesPublic implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query("UPDATE site SET is_public = '1'");
    }
}
