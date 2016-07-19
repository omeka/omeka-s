<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class ChangeQueryToItemPool implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('ALTER TABLE site CHANGE query item_pool LONGTEXT NOT NULL COMMENT \'(DC2Type:json_array)\';');
    }
}
