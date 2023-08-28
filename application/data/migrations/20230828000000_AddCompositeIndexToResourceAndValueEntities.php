<?php declare(strict_types=1);

namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddCompositeIndexToResourceAndValueEntities implements MigrationInterface
{
    public function up(Connection $conn)
    {
        try {
            $conn->executeStatement('ALTER TABLE `resource` ADD INDEX `idx_public_type_id_title` (`is_public`,`resource_type`,`id`,`title` (190));');
        } catch (\Exception $e) {
            // Index exists.
        }

        try {
            $conn->executeStatement('ALTER TABLE `value` ADD INDEX `idx_public_resource_property` (`is_public`,`resource_id`,`property_id`);');
        } catch (\Exception $e) {
            // Index exists.
        }
    }
}
