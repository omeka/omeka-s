<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class DropIsShareable implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('ALTER TABLE item DROP is_shareable;');
    }
}
