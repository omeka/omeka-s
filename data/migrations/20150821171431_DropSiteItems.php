<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class DropSiteItems extends AbstractMigration
{
    public function up()
    {
        $connection = $this->getConnection();
        $connection->exec('DROP TABLE site_item');
    }
}
