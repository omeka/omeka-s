<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AlterSiteItemSet implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('ALTER TABLE site_item_set DROP FOREIGN KEY FK_D4CE134960278D7;');
        $conn->query('ALTER TABLE site_item_set DROP FOREIGN KEY FK_D4CE134F6BD1646;');
        $conn->query('ALTER TABLE site_item_set CHANGE item_set_id item_set_id INT NOT NULL, CHANGE site_id site_id INT NOT NULL;');
        $conn->query('ALTER TABLE site_item_set ADD CONSTRAINT FK_D4CE134960278D7 FOREIGN KEY (item_set_id) REFERENCES item_set (id) ON DELETE CASCADE;');
        $conn->query('ALTER TABLE site_item_set ADD CONSTRAINT FK_D4CE134F6BD1646 FOREIGN KEY (site_id) REFERENCES site (id) ON DELETE CASCADE;');
    }
}
