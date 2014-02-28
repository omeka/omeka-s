<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;
use Omeka\Db\Migration\TableResolver;
use Doctrine\DBAL\Connection;

class ModuleTable extends AbstractMigration
{
    public function up(Connection $conn, TableResolver $resolver)
    {
        $tableName = $resolver->getTableName('Omeka\Model\Entity\Module');
        $conn->exec("CREATE TABLE $tableName (id VARCHAR(255) NOT NULL, is_active TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;");
    }
}
