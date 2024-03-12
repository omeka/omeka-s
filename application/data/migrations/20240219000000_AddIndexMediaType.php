<?php declare(strict_types=1);

namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddIndexMediaType implements MigrationInterface
{
    public function up(Connection $conn)
    {
        try {
            $conn->executeStatement('ALTER TABLE `media` CHANGE `media_type` `media_type` varchar(190) COLLATE "utf8mb4_unicode_ci" NULL AFTER `source`;');
            $conn->executeStatement('ALTER TABLE `media` ADD INDEX `media_type` (`media_type`);');
        } catch (\Exception $e) {
            // Index exists.
        }
    }
}
