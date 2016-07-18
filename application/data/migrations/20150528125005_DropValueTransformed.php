<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class DropValueTransformed implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('ALTER TABLE value DROP value_transformed;');
    }
}
