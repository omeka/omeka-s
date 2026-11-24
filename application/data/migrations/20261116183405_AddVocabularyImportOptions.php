<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddVocabularyImportOptions implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->executeStatement('ALTER TABLE vocabulary ADD import_options LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\';');
    }
}
