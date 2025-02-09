<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class FixFulltextResourceLength implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->exec('ALTER TABLE fulltext_search CHANGE resource resource VARCHAR(190) NOT NULL');
    }
}
