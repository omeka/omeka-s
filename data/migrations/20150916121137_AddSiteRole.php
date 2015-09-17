<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class AddSiteRole extends AbstractMigration
{
    public function up()
    {
        $connection = $this->getConnection();
        $connection->query('ALTER TABLE site_permission ADD role VARCHAR(80) NOT NULL, DROP admin, DROP attach, DROP edit;');
    }
}
