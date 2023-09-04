<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddItemPrimaryMedia implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $sql = <<<'SQL'
UPDATE `item`
INNER JOIN `media` ON `media`.`item_id` = `item`.`id`
SET
    `item`.`primary_media_id` = `media`.`id`
WHERE `item`.`primary_media_id` IS NULL
    AND `media`.`position` = 1
;
SQL;
        $conn->executeStatement($sql);
    }
}
