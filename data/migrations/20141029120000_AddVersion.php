<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class AddVersion extends AbstractMigration
{
    public function up()
    {
        $settings = $this->getServiceLocator()->get('Omeka\Settings');
        $settings->set('version', '0.1.0');
    }
}
