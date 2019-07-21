<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class ItemShowcaseCase implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->exec('UPDATE site_page_block SET layout = "itemShowcase" WHERE layout = "itemShowCase";');
    }
}
