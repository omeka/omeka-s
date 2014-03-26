<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;
use Omeka\Db\Migration\TableResolver;
use Doctrine\DBAL\Connection;

class ModuleVersion extends AbstractMigration
{
    public function up(Connection $conn, TableResolver $resolver)
    {
        $tableName = $resolver->getTableName('Omeka\Model\Entity\Module');
        $conn->exec("ALTER TABLE $tableName ADD version VARCHAR(255) NOT NULL;");
    }
}
