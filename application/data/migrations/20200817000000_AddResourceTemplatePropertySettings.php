<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddResourceTemplatePropertySettings implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $sql = <<<SQL
ALTER TABLE `resource_template_property`
ADD `settings` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '(DC2Type:json_array)';
SQL;
        $conn->exec($sql);

        $sql = <<<SQL
UPDATE `resource_template_property`
SET `settings` = '[]';
SQL;
        $conn->exec($sql);
    }
}
