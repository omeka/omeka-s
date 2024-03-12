<?php declare(strict_types=1);

namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class LimitFieldsTo190Characters implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $sql = <<<'SQL'
ALTER TABLE `asset`
CHANGE `media_type` `media_type` varchar(190) COLLATE 'utf8mb4_unicode_ci' NOT NULL AFTER `name`,
CHANGE `extension` `extension` varchar(190) COLLATE 'utf8mb4_unicode_ci' NULL AFTER `storage_id`;

ALTER TABLE `job`
CHANGE `pid` `pid` varchar(190) COLLATE 'utf8mb4_unicode_ci' NULL AFTER `owner_id`,
CHANGE `status` `status` varchar(190) COLLATE 'utf8mb4_unicode_ci' NULL AFTER `pid`,
CHANGE `class` `class` varchar(190) COLLATE 'utf8mb4_unicode_ci' NOT NULL AFTER `status`;

ALTER TABLE `media`
CHANGE `ingester` `ingester` varchar(190) COLLATE 'utf8mb4_unicode_ci' NOT NULL AFTER `item_id`,
CHANGE `renderer` `renderer` varchar(190) COLLATE 'utf8mb4_unicode_ci' NOT NULL AFTER `ingester`,
CHANGE `media_type` `media_type` varchar(190) COLLATE 'utf8mb4_unicode_ci' NULL AFTER `source`,
CHANGE `extension` `extension` varchar(190) COLLATE 'utf8mb4_unicode_ci' NULL AFTER `storage_id`;

ALTER TABLE `module`
CHANGE `version` `version` varchar(190) COLLATE 'utf8mb4_unicode_ci' NOT NULL AFTER `is_active`;

ALTER TABLE `resource`
CHANGE `resource_type` `resource_type` varchar(190) COLLATE 'utf8mb4_unicode_ci' NOT NULL AFTER `modified`;

ALTER TABLE `resource_template_property`
CHANGE `default_lang` `default_lang` varchar(190) COLLATE 'utf8mb4_unicode_ci' NULL AFTER `is_private`;

ALTER TABLE `site_page`
CHANGE `layout` `layout` varchar(190) COLLATE 'utf8mb4_unicode_ci' NULL AFTER `modified`;

ALTER TABLE `value`
CHANGE `type` `type` varchar(190) COLLATE 'utf8mb4_unicode_ci' NOT NULL AFTER `value_resource_id`,
CHANGE `lang` `lang` varchar(190) COLLATE 'utf8mb4_unicode_ci' NULL AFTER `type`;

SQL;
        $conn->executeStatement($sql);
    }
}
