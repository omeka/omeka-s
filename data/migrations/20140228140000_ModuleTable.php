<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class ModuleTable extends AbstractMigration
{
    public function up()
    {
        $tableName = $this->getDbHelper()->getTableNameForEntity('Omeka\Model\Entity\Module');
        $statement = "CREATE TABLE $tableName (id VARCHAR(255) NOT NULL, is_active TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB";
        $this->getDbHelper()->execute($statement);
    }
}
