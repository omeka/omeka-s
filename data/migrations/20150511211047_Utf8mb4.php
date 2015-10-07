<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class Utf8mb4 extends AbstractMigration
{
    public function up()
    {
        $connection = $this->getConnection();

        // Resize columns/indexes to prevent errors when converting charset
        $connection->query('ALTER TABLE user CHANGE username username VARCHAR(190) NOT NULL, CHANGE email email VARCHAR(190) NOT NULL, CHANGE name name VARCHAR(190) NOT NULL, CHANGE role role VARCHAR(190) NOT NULL');
        $connection->query('ALTER TABLE site CHANGE slug slug VARCHAR(190) NOT NULL, CHANGE theme theme VARCHAR(190) NOT NULL, CHANGE title title VARCHAR(190) NOT NULL');
        $connection->query('ALTER TABLE vocabulary CHANGE namespace_uri namespace_uri VARCHAR(190) NOT NULL, CHANGE prefix prefix VARCHAR(190) NOT NULL');
        $connection->query('ALTER TABLE resource_template CHANGE `label` `label` VARCHAR(190) NOT NULL');
        $connection->query('ALTER TABLE setting CHANGE id id VARCHAR(190) NOT NULL');
        $connection->query('ALTER TABLE module CHANGE id id VARCHAR(190) NOT NULL');
        $connection->query('ALTER TABLE site_page CHANGE slug slug VARCHAR(190) NOT NULL, CHANGE title title VARCHAR(190) NOT NULL');

        $tables = [
            'api_key','item','item_set','job','media','migration','module',
            'resource','resource_template','resource_template_property',
            'setting','site','site_block_attachment','site_item','site_page',
            'site_page_block','site_permission','user','value','vocabulary',
        ];

        foreach ($tables as $table) {
            $connection->query("ALTER TABLE $table CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }

        // Must alter these tables separately or there could be an integrity
        // constraint violation for local_names that differ only in case.
        $connection->query('ALTER TABLE `property` MODIFY `local_name` VARCHAR(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL;');
        $connection->query('ALTER TABLE `property` MODIFY `label` VARCHAR(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;');
        $connection->query('ALTER TABLE `property` MODIFY `comment` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;');

        $connection->query('ALTER TABLE `resource_class` MODIFY `local_name` VARCHAR(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL;');
        $connection->query('ALTER TABLE `resource_class` MODIFY `label` VARCHAR(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;');
        $connection->query('ALTER TABLE `resource_class` MODIFY `comment` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;');
    }
}
