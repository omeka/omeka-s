<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class SiteUserOnDelete implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->exec('ALTER TABLE site DROP FOREIGN KEY FK_694309E47E3C61F9');
        $conn->exec('ALTER TABLE site ADD CONSTRAINT FK_694309E47E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id) ON DELETE SET NULL');
    }
}
