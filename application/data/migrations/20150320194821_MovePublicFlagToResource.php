<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class MovePublicFlagToResource implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('ALTER TABLE resource ADD is_public TINYINT(1) NOT NULL');
        $conn->query('ALTER TABLE item DROP is_public');
        $conn->query('ALTER TABLE media DROP is_public');
    }
}
