<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class BasicUserProperties extends AbstractMigration
{
    public function up()
    {
        $tableName = $this->getDbHelper()->getTableNameForEntity('Omeka\Model\Entity\User');
        $statements = array(
            "ALTER TABLE $tableName ADD email VARCHAR(255) NOT NULL,ADD name VARCHAR(255) NOT NULL, ADD created DATETIME NOT NULL",
            "CREATE UNIQUE INDEX UNIQ_AACC6A08E7927C74 ON $tableName (email)",
        );
        $this->getDbHelper()->execute($statements);
    }
}
