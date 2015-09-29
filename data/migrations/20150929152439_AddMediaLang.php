<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class AddMediaLang extends AbstractMigration
{
    public function up()
    {
        $connection = $this->getConnection();
        $connection->query('ALTER TABLE media ADD lang VARCHAR(190) DEFAULT NULL');
    }
}
