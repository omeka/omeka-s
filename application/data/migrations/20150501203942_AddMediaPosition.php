<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddMediaPosition implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('ALTER TABLE media ADD position INT DEFAULT NULL');
        $conn->query('CREATE INDEX item_position ON media (item_id, position)');
    }
}
