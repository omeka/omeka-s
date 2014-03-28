<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class OptionJsonValue extends AbstractMigration
{
    public function up()
    {
        $tableName = $this->getDbHelper()->getTableNameForEntity('Omeka\Model\Entity\Option');
        $statement = "ALTER TABLE $tableName CHANGE value value LONGTEXT NOT NULL COMMENT '(DC2Type:json_array)'";
        $this->getDbHelper()->execute($statement);
    }
}
