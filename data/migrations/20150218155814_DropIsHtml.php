<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class DropIsHtml extends AbstractMigration
{
    public function up()
    {
        $connection = $this->getConnection();
        $connection->query('ALTER TABLE value DROP is_html;');
    }
}
