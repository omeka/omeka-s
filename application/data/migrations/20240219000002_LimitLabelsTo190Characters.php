<?php declare(strict_types=1);

namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class LimitLabelsTo190Characters implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $sql = <<<'SQL'
ALTER TABLE `api_key`
CHANGE `label` `label` varchar(190) COLLATE 'utf8mb4_unicode_ci' NOT NULL AFTER `owner_id`;

ALTER TABLE `asset`
CHANGE `name` `name` varchar(190) COLLATE 'utf8mb4_unicode_ci' NOT NULL AFTER `owner_id`;

ALTER TABLE `property`
CHANGE `label` `label` varchar(190) COLLATE 'utf8mb4_unicode_ci' NOT NULL AFTER `local_name`;

ALTER TABLE `resource_class`
CHANGE `label` `label` varchar(190) COLLATE 'utf8mb4_unicode_ci' NOT NULL AFTER `local_name`;

ALTER TABLE `resource_template_property`
CHANGE `alternate_label` `alternate_label` varchar(190) COLLATE 'utf8mb4_unicode_ci' NULL AFTER `property_id`;

ALTER TABLE `vocabulary`
CHANGE `label` `label` varchar(190) COLLATE 'utf8mb4_unicode_ci' NOT NULL AFTER `prefix`;

SQL;
        $conn->executeStatement($sql);
    }
}
