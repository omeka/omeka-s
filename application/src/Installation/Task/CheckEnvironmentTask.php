<?php
namespace Omeka\Installation\Task;

use Omeka\Installation\Installer;

/**
 * Check environment task.
 */
class CheckEnvironmentTask implements TaskInterface
{
    public function perform(Installer $installer)
    {
        $environment = $installer->getServiceLocator()->get('Omeka\Environment');
        if (!$environment->isCompatible()) {
            foreach ($environment->getErrorMessages() as $errorMessage) {
                $installer->addError($errorMessage);
            }
        }
    }
}
