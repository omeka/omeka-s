<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddResourceTemplatePropertyPosition implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('ALTER TABLE resource_template_property ADD position INT DEFAULT NULL;');
    }
}
