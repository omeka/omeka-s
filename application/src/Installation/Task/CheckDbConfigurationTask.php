<?php
namespace Omeka\Installation\Task;

use Omeka\Installation\Manager;

/**
 * Check database configuration task.
 */
class CheckDbConfigurationTask implements TaskInterface
{
    public function perform(Manager $manager)
    {
        try {
            $manager->getServiceLocator()->get('Omeka\Connection')->connect();
        } catch (\Exception $e) {
            $manager->addError($e->getMessage());
            return;
        }
    }
}
