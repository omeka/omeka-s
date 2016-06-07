<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class DropIsHtml extends MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('ALTER TABLE value DROP is_html;');
    }
}
