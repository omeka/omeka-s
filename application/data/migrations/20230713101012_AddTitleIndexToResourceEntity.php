<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddTitleIndexToResourceEntity implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('CREATE INDEX title ON resource (title(190));');
    }
}
