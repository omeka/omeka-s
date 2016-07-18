<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class ResourceTemplateLabelUniqueIndex implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('CREATE UNIQUE INDEX UNIQ_39ECD52EEA750E8 ON resource_template (`label`);');
    }
}
