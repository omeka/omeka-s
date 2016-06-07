<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddMediaLang implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('ALTER TABLE media ADD lang VARCHAR(190) DEFAULT NULL');
    }
}
