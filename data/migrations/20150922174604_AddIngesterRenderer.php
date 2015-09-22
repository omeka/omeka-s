<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class AddIngesterRenderer extends AbstractMigration
{
    public function up()
    {
        $connection = $this->getConnection();
        $connection->query('ALTER TABLE media ADD renderer VARCHAR(255) NOT NULL, CHANGE type ingester VARCHAR(255) NOT NULL;');
    }
}
