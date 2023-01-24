<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddPageGridLayout implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('ALTER TABLE site_page ADD columns SMALLINT DEFAULT NULL;');
        $conn->query('ALTER TABLE site_page_block ADD location VARCHAR(2) DEFAULT NULL;');
    }
}
