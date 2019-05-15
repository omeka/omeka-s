<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddOwnerAndIsPublic implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->exec('ALTER TABLE fulltext_search ADD owner_id INT DEFAULT NULL, ADD is_public TINYINT(1) NOT NULL;');
        $conn->exec('ALTER TABLE fulltext_search ADD CONSTRAINT FK_AA31FE4A7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id) ON DELETE SET NULL;');
        $conn->exec('CREATE INDEX IDX_AA31FE4A7E3C61F9 ON fulltext_search (owner_id);');
    }
}
