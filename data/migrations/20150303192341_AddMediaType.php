<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddMediaType implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('ALTER TABLE media ADD media_type VARCHAR(255) DEFAULT NULL;');
    }
}
