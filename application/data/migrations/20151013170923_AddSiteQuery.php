<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddSiteQuery implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('ALTER TABLE site ADD query LONGTEXT NOT NULL COMMENT \'(DC2Type:json_array)\';');
    }
}
