<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddUriColumn implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('ALTER TABLE value ADD uri LONGTEXT DEFAULT NULL');
        $conn->query('UPDATE value SET uri = value, value = uri_label WHERE type = "uri"');
        $conn->query('ALTER TABLE value DROP uri_label;');
    }
}
