<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class AddHasStoredFiles extends AbstractMigration
{
    public function up()
    {
        $connection = $this->getConnection();
        $connection->query('ALTER TABLE media ADD has_original TINYINT(1) NOT NULL, ADD has_thumbnails TINYINT(1) NOT NULL;');
    }
}
