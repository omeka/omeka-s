<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;
use Omeka\Db\Migration\TableResolver;
use Doctrine\DBAL\Connection;

class BasicUserProperties extends AbstractMigration
{
	public function up(Connection $conn, TableResolver $resolver)
	{
        $tableName = $resolver->getTableName("Omeka\Model\Entity\User");
        $sql = "ALTER TABLE $tableName ADD email VARCHAR(255) NOT NULL,
                ADD name VARCHAR(255) NOT NULL, ADD created DATETIME NOT NULL;
                CREATE UNIQUE INDEX UNIQ_AACC6A08E7927C74 ON $tableName (email);
        ";
        $conn->exec($sql);
	}
}