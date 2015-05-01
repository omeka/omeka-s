<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class AddMediaPosition extends AbstractMigration
{
    public function up()
    {
        $connection = $this->getConnection();
        $connection->query('ALTER TABLE media ADD position INT DEFAULT NULL');
        $connection->query('CREATE INDEX item_position ON media (item_id, position)');
    }
}
