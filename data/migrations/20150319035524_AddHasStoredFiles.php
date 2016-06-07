<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddHasStoredFiles implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('ALTER TABLE media ADD has_original TINYINT(1) NOT NULL, ADD has_thumbnails TINYINT(1) NOT NULL;');
    }
}
