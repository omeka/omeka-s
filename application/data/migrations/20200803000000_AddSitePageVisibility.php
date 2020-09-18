<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddSitePageVisibility implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $sql = <<<SQL
ALTER TABLE `site_page` ADD `is_public` TINYINT(1) NOT NULL AFTER `title`;
SQL;
        $conn->exec($sql);

        $sql = <<<SQL
UPDATE `site_page` SET `is_public` = 1;
SQL;
        $conn->exec($sql);
    }
}
