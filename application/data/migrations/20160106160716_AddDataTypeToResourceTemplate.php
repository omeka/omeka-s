<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddDataTypeToResourceTemplate implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('ALTER TABLE resource_template_property ADD data_type VARCHAR(255) NOT NULL;');
        $conn->query('UPDATE resource_template_property SET data_type = "normal";');
    }
}
