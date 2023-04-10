<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class ValueAnnotationsSetting implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $conn->update('site_setting', ['value' => '"collapsed"'], ['id' => 'show_value_annotations', 'value' => '"1"']);
        $conn->delete('site_setting', ['id' => 'show_value_annotations', 'value' => '"0"']);
    }
}
