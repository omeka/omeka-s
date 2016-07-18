<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddIngesterRenderer implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('ALTER TABLE media ADD renderer VARCHAR(255) NOT NULL, CHANGE type ingester VARCHAR(255) NOT NULL;');
    }
}
