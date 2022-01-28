<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddValueAnnotation implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('CREATE TABLE value_annotation (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;');
        $conn->query('ALTER TABLE value_annotation ADD CONSTRAINT FK_C03BA4EBF396750 FOREIGN KEY (id) REFERENCES resource (id) ON DELETE CASCADE;');
        $conn->query('ALTER TABLE value ADD value_annotation_id INT DEFAULT NULL;');
        $conn->query('ALTER TABLE value ADD CONSTRAINT FK_1D7758349B66727E FOREIGN KEY (value_annotation_id) REFERENCES value_annotation (id) ON DELETE SET NULL;');
        $conn->query('CREATE UNIQUE INDEX UNIQ_1D7758349B66727E ON value (value_annotation_id);');
    }
}
