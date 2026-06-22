<?php

namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddIndexIsPublic implements MigrationInterface
{
    public function up(Connection $conn)
    {
        try {
            $conn->executeStatement('ALTER TABLE `resource` ADD INDEX `is_public` (`is_public`);');
        } catch (\Exception $e) {
            // Index exists.
        }

        try {
            $conn->executeStatement('ALTER TABLE `value` ADD INDEX `is_public` (`is_public`);');
        } catch (\Exception $e) {
            // Index exists.
        }

        try {
            $conn->executeStatement('ALTER TABLE `site_page` ADD INDEX `is_public` (`is_public`);');
        } catch (\Exception $e) {
            // Index exists.
        }
    }
}
