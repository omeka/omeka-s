<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddValueAnnotation implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->query('CREATE TABLE annotation (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;');
        $conn->query('ALTER TABLE annotation ADD CONSTRAINT FK_2E443EF2BF396750 FOREIGN KEY (id) REFERENCES resource (id) ON DELETE CASCADE;');
        $conn->query('ALTER TABLE value ADD annotation_id INT DEFAULT NULL;');
        $conn->query('ALTER TABLE value ADD CONSTRAINT FK_1D775834E075FC54 FOREIGN KEY (annotation_id) REFERENCES annotation (id);');
        $conn->query('CREATE UNIQUE INDEX UNIQ_1D775834E075FC54 ON value (annotation_id);');
    }
}
