<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddValueIsPublic implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->exec('ALTER TABLE value ADD is_public TINYINT(1) NOT NULL;');
        $conn->exec('UPDATE value SET is_public = 1;');
    }
}
