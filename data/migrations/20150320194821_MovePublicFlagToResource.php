<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class MovePublicFlagToResource extends AbstractMigration
{
    public function up()
    {
        $connection = $this->getConnection();
        $connection->query('ALTER TABLE resource ADD is_public TINYINT(1) NOT NULL');
        $connection->query('ALTER TABLE item DROP is_public');
        $connection->query('ALTER TABLE media DROP is_public');
    }
}
