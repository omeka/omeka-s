<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddLangToSitePage implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->exec('ALTER TABLE site_page ADD lang VARCHAR(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER title;');
    }
}
