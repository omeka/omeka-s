<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class AddTimeZone implements MigrationInterface
{
    public function up(Connection $conn)
    {
        $settings = $this->getServiceLocator()->get('Omeka\Settings');
        $settings->set('time_zone', date_default_timezone_get());
    }
}
