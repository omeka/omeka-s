<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddOnDeleteSetNull implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('ALTER TABLE value DROP FOREIGN KEY FK_1D7758349B66727E;');
        $conn->query('ALTER TABLE value ADD CONSTRAINT FK_1D7758349B66727E FOREIGN KEY (value_annotation_id) REFERENCES value_annotation (id) ON DELETE SET NULL;');
    }
}
