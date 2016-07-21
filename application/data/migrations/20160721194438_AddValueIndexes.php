<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddValueIndexes implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->exec('ALTER TABLE value ADD INDEX (value(190)), ADD INDEX (uri(190))');
    }
}
