<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class UpdateResourceTypes extends AbstractMigration
{
    public function up()
    {
        $connection = $this->getConnection();
        $connection->query("UPDATE resource SET resource_type = REPLACE(resource_type, 'Omeka\\\Model\\\Entity', 'Omeka\\\Entity')");
    }
}
