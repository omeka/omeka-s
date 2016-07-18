<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddTimeZone implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $settings = $this->getServiceLocator()->get('Omeka\Settings');
        $timezone = ini_get('date.timezone');
        if (!$timezone) {
            $timezone = 'UTC';
        }
        $settings->set('time_zone', $timezone);
    }
}
