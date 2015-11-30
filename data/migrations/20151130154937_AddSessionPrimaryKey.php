<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class AddSessionPrimaryKey extends AbstractMigration
{
    public function up()
    {
        $connection = $this->getConnection();
        $connection->exec('TRUNCATE TABLE session;');
        $connection->exec('ALTER TABLE session DROP PRIMARY KEY;');
        $connection->exec('ALTER TABLE session ADD PRIMARY KEY (id);');
    }
}
