<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class RemoveFile extends AbstractMigration
{
    public function up()
    {
        $connection = $this->getConnection();

        $connection->query('ALTER TABLE media DROP FOREIGN KEY FK_6A2CA10C93CB796C, DROP file_id, ADD filename VARCHAR(255) DEFAULT NULL');
        $connection->query('DROP TABLE file');
    }
}
