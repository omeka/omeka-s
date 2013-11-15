<?php
namespace Omeka\Db\Migration;

use Doctrine\DBAL\Connection;

interface MigrationInterface
{
    public function up(Connection $conn);

    public function down(Connection $conn);
}
