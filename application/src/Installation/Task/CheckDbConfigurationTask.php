<?php
namespace Omeka\Installation\Task;

use Omeka\Installation\Installer;

/**
 * Check database configuration task.
 */
class CheckDbConfigurationTask implements TaskInterface
{
    public function perform(Installer $installer)
    {
        try {
            $installer->getServiceLocator()->get('Omeka\Connection')->connect();
        } catch (\Exception $e) {
            $installer->addError($e->getMessage());
            return;
        }
    }
}
