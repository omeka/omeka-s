<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class PasswordHash extends AbstractMigration
{
    public function up()
    {
        $tableName = $this->getDbHelper()->getTableName('Omeka\Model\Entity\User');
        $statement = "ALTER TABLE $tableName ADD password_hash VARCHAR(60) DEFAULT NULL";
        $this->getDbHelper()->execute($statement);
    }
}
