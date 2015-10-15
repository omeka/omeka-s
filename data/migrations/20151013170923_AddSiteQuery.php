<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class AddSiteQuery extends AbstractMigration
{
    public function up()
    {
        $connection = $this->getConnection();
        $connection->query('ALTER TABLE site ADD query LONGTEXT NOT NULL COMMENT \'(DC2Type:json_array)\';');
    }
}
