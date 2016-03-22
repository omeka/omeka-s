<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class SensitizePasswordCreation extends AbstractMigration
{
    public function up()
    {
        $connection = $this->getConnection();
        $connection->query('ALTER TABLE password_creation CHANGE id id VARCHAR(32) NOT NULL COLLATE utf8mb4_bin');
    }
}
