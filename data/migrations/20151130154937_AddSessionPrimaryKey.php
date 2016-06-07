<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddSessionPrimaryKey implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->exec('TRUNCATE TABLE session;');
        $conn->exec('ALTER TABLE session DROP PRIMARY KEY;');
        $conn->exec('ALTER TABLE session ADD PRIMARY KEY (id);');
    }
}
