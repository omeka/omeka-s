<?php
namespace Omeka\Db\Migrations;

use Omeka\Db\Migration\AbstractMigration;
use Omeka\Installation\Task\InstallDefaultTemplatesTask;

class AddBaseResourceTemplate extends AbstractMigration
{
    public function up()
    {
        $task = new InstallDefaultTemplatesTask;
        $task->setServiceLocator($this->getServiceLocator());
        $task->installTemplate('Base Template');
    }
}
