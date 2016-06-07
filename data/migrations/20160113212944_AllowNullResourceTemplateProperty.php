<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AllowNullResourceTemplateProperty implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('ALTER TABLE resource_template_property CHANGE data_type data_type VARCHAR(255) DEFAULT NULL;');
        $conn->query('UPDATE resource_template_property SET data_type = NULL;');
    }
}
