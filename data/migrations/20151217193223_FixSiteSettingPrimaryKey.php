<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class FixSiteSettingPrimaryKey extends AbstractMigration
{
    public function up()
    {
        $connection = $this->getConnection();
        $connection->query('ALTER TABLE site_setting DROP PRIMARY KEY, ADD PRIMARY KEY (id, site_id)');
    }
}
