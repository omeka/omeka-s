<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddSiteHomepage implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->exec('ALTER TABLE site ADD homepage_id INT DEFAULT NULL');
        $conn->exec('ALTER TABLE site ADD CONSTRAINT FK_694309E4571EDDA FOREIGN KEY (homepage_id) REFERENCES site_page (id) ON DELETE SET NULL');
        $conn->exec('CREATE UNIQUE INDEX UNIQ_694309E4571EDDA ON site (homepage_id);');
    }
}
