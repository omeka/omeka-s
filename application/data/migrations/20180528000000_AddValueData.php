<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddValueData implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->exec('
ALTER TABLE value
ADD data VARCHAR(190) DEFAULT NULL,
CHANGE type type VARCHAR(190) NOT NULL,
CHANGE lang lang VARCHAR(190) DEFAULT NULL;
        ');
        $conn->exec('
CREATE INDEX IDX_1D7758348CDE5729 ON value (type);
        ');
        $conn->exec('
CREATE INDEX IDX_1D77583431098462 ON value (lang);
        ');
    }
}
