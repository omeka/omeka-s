<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class ResourceTemplatePropertyDefaultLang implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('ALTER TABLE resource_template_property ADD default_lang VARCHAR(255) DEFAULT NULL;');
    }
}
