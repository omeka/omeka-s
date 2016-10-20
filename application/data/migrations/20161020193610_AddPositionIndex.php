<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddPositionIndex implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('CREATE INDEX position ON site_item_set (position);');
    }
}
