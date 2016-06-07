<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class FixSiteSettingPrimaryKey implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('ALTER TABLE site_setting DROP PRIMARY KEY, ADD PRIMARY KEY (id, site_id)');
    }
}
