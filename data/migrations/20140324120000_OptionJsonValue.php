<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;
use Omeka\Db\Migration\TableResolver;
use Doctrine\DBAL\Connection;

class OptionJsonValue extends AbstractMigration
{
    public function up(Connection $conn, TableResolver $resolver)
    {
        $tableName = $resolver->getTableName('Omeka\Model\Entity\Option');
        $conn->exec("ALTER TABLE $tableName CHANGE value value LONGTEXT NOT NULL COMMENT '(DC2Type:json_array)';");
    }
}
