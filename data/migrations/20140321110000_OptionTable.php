<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class OptionTable extends AbstractMigration
{
    public function up()
    {
        $tableName = $this->getDbHelper()->getTableName('Omeka\Model\Entity\Option');
        $statement = "CREATE TABLE $tableName (id VARCHAR(255) NOT NULL, value LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB";
        $this->getDbHelper()->execute($statement);
    }
}
