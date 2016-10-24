<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddSiteItemSetTable implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('CREATE TABLE site_item_set (id INT AUTO_INCREMENT NOT NULL, site_id INT DEFAULT NULL, item_set_id INT DEFAULT NULL, INDEX IDX_D4CE134F6BD1646 (site_id), INDEX IDX_D4CE134960278D7 (item_set_id), UNIQUE INDEX UNIQ_D4CE134F6BD1646960278D7 (site_id, item_set_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;');
        $conn->query('ALTER TABLE site_item_set ADD CONSTRAINT FK_D4CE134F6BD1646 FOREIGN KEY (site_id) REFERENCES site (id) ON DELETE SET NULL;');
        $conn->query('ALTER TABLE site_item_set ADD CONSTRAINT FK_D4CE134960278D7 FOREIGN KEY (item_set_id) REFERENCES item_set (id) ON DELETE SET NULL;');
        $conn->query('ALTER TABLE site DROP item_sets;');
    }
}
