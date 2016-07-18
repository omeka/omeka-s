<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class ResourceTemplatePropertyUniqueIndex implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('CREATE UNIQUE INDEX UNIQ_4689E2F116131EA549213EC ON resource_template_property (resource_template_id, property_id);');
    }
}
