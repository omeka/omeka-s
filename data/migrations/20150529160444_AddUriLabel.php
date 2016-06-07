<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddUriLabel implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('ALTER TABLE value ADD uri_label VARCHAR(255) DEFAULT NULL;');
    }
}
