<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddSiteRole implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('ALTER TABLE site_permission ADD role VARCHAR(80) NOT NULL, DROP admin, DROP attach, DROP edit;');
    }
}
