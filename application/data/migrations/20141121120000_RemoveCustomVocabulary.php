<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class RemoveCustomVocabulary implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->delete('vocabulary', ['prefix' => 'omeka']);
    }
}
