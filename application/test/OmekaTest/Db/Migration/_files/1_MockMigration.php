<?php
namespace OmekaTest\Db\Migration;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class MockMigration implements MigrationInterface
{
    public function up(Connection $conn)
    {
    }
}
