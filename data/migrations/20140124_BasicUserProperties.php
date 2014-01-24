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
        $sql = "ALTER TABLE omeka_user ADD email LONGTEXT NOT NULL, ADD name LONGTEXT NOT NULL,
                ADD created DATETIME NOT NULL, CHANGE username username VARCHAR(30) NOT NULL;
                CREATE UNIQUE INDEX UNIQ_AACC6A08E7927C74 ON omeka_user (email);";
        $conn->exec($sql);
	}
}