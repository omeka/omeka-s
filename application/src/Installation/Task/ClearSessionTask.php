<?php
namespace Omeka\Installation\Task;

use Omeka\Installation\Installer;

/**
 * Task to clear identity from the session.
 */
class ClearSessionTask implements TaskInterface
{
    public function perform(Installer $installer)
    {
        $auth = $installer->getServiceLocator()->get('Omeka\AuthenticationService');
        $auth->getStorage()->clear();
    }
}
