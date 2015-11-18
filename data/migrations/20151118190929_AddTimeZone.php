<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class AddTimeZone extends AbstractMigration
{
    public function up()
    {
        $settings = $this->getServiceLocator()->get('Omeka\Settings');
        $settings->set('time_zone', date_default_timezone_get());
    }
}
