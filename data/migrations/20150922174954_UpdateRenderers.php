<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class UpdateRenderers extends AbstractMigration
{
    public function up()
    {
        $connection = $this->getConnection();
        $connection->query('UPDATE media SET renderer = ingester');
        $connection->query("UPDATE media SET renderer = 'file' WHERE renderer IN ('url','upload')");
    }
}
