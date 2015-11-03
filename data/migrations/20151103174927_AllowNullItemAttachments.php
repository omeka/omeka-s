<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class AllowNullItemAttachments extends AbstractMigration
{
    public function up()
    {
        $connection = $this->getConnection();
        $connection->query('ALTER TABLE site_block_attachment DROP FOREIGN KEY FK_236473FE126F525E');
        $connection->query('ALTER TABLE site_block_attachment CHANGE item_id item_id INT DEFAULT NULL');
        $connection->query('ALTER TABLE site_block_attachment ADD CONSTRAINT FK_236473FE126F525E FOREIGN KEY (item_id) REFERENCES item (id) ON DELETE SET NULL');
    }
}
