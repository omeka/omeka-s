<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class MakeSitesPublic extends AbstractMigration
{
    public function up()
    {
        $connection = $this->getConnection();
        $connection->query("UPDATE site SET is_public = '1'");
    }
}
