<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class AddUriColumn extends AbstractMigration
{
    public function up()
    {
        $connection = $this->getConnection();
        $connection->query('ALTER TABLE value ADD uri LONGTEXT DEFAULT NULL');
        $connection->query('UPDATE value SET uri = value, value = uri_label WHERE type = "uri"');
        $connection->query('ALTER TABLE value DROP uri_label;');
    }
}
