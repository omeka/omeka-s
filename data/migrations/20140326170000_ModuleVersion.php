<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class ModuleVersion extends AbstractMigration
{
    public function up()
    {
        $tableName = $this->getDbHelper()->getTableName('Omeka\Model\Entity\Module');
        $statement = "ALTER TABLE $tableName ADD version VARCHAR(255) NOT NULL;";
        $this->getDbHelper()->execute($statement);
    }
}
