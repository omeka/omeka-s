<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddIsPrivateToTemplate implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->exec('ALTER TABLE resource_template_property ADD is_private TINYINT(1) NOT NULL;');
    }
}
