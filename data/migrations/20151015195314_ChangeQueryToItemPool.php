<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class ChangeQueryToItemPool extends AbstractMigration
{
    public function up()
    {
        $connection = $this->getConnection();
        $connection->query('ALTER TABLE site CHANGE query item_pool LONGTEXT NOT NULL COMMENT \'(DC2Type:json_array)\';');
    }
}
