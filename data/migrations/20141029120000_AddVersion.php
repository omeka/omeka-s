<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;

class AddVersion extends AbstractMigration
{
    public function up()
    {
        $options = $this->getServiceLocator()->get('Omeka\Options');
        $options->set('version', '0.1.0');
    }
}
