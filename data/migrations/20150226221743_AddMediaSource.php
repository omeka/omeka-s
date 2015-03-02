<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class AddMediaSource extends AbstractMigration
{
    public function up()
    {
        $connection = $this->getConnection();

        $connection->query("ALTER TABLE media ADD source LONGTEXT DEFAULT NULL");
    }
}
