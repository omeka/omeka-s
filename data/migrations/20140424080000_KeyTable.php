<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class KeyTable extends AbstractMigration
{
    public function up()
    {
        $tableName = $this->getDbHelper()
            ->getTableNameForEntity('Omeka\Model\Entity\Key');
        $statement = "
        CREATE TABLE $tableName (
            id VARCHAR(255) NOT NULL,
            user_id INT DEFAULT NULL,
            INDEX IDX_D76D40D8A76ED395 (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
        ALTER TABLE $tableName
        ADD CONSTRAINT FK_D76D40D8A76ED395
        FOREIGN KEY (user_id)
        REFERENCES omeka_user (id);";
        $this->getDbHelper()->execute($statement);
    }
}
