<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class UserRole extends AbstractMigration
{
    public function up()
    {
        $tableName = $this->getDbHelper()->getTableNameForEntity('Omeka\Model\Entity\User');
        $statement = "ALTER TABLE $tableName ADD role VARCHAR(255) NOT NULL";
        $this->getDbHelper()->execute($statement);
    }
}
