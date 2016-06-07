<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class UpdateResourceTypes implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query("UPDATE resource SET resource_type = REPLACE(resource_type, 'Omeka\\\Model\\\Entity', 'Omeka\\\Entity')");
    }
}
