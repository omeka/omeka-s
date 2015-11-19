<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class AddSitePageDatetimes extends AbstractMigration
{
    public function up()
    {
        $connection = $this->getConnection();
        $connection->query('ALTER TABLE site_page ADD created DATETIME NOT NULL, ADD modified DATETIME DEFAULT NULL;');
    }
}
