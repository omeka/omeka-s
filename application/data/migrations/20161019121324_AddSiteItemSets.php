<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddSiteItemSets implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('ALTER TABLE site ADD item_sets LONGTEXT NOT NULL COMMENT \'(DC2Type:json_array)\';');
    }
}
