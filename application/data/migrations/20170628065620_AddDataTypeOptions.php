<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddDataTypeOptions implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('ALTER TABLE resource_template_property ADD data_type_options LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json_array)\';');
    }
}
