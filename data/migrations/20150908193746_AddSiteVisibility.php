<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class AddSiteVisibility extends AbstractMigration
{
    public function up()
    {
        $connection = $this->getConnection();
        $connection->exec('ALTER TABLE site ADD is_public TINYINT(1) NOT NULL;');
    }
}
