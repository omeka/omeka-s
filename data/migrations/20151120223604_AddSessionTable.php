<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class AddSessionTable extends AbstractMigration
{
    public function up()
    {
        $connection = $this->getConnection();
        $connection->query('CREATE TABLE session (id VARCHAR(190) NOT NULL, modified INT NOT NULL, data LONGBLOB NOT NULL, PRIMARY KEY(id, modified)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;');
    }
}
