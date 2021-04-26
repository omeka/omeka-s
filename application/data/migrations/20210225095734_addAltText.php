<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class addAltText implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('ALTER TABLE asset ADD alt_text LONGTEXT DEFAULT NULL');
        $conn->query('ALTER TABLE media ADD alt_text LONGTEXT DEFAULT NULL');
    }
}
