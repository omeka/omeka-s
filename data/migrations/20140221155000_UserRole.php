<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;
use Omeka\Db\Migration\TableResolver;
use Doctrine\DBAL\Connection;

class UserRole extends AbstractMigration
{
    public function up(Connection $conn, TableResolver $resolver)
    {
        $tableName = $resolver->getTableName('Omeka\Model\Entity\User');
        $conn->exec("ALTER TABLE $tableName ADD role VARCHAR(255) NOT NULL");
    }
}
