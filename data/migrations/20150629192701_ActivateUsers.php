<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class ActivateUsers extends AbstractMigration
{
    public function up()
    {
        $connection = $this->getConnection();
        $connection->query("UPDATE user SET is_active = '1'");
    }
}
