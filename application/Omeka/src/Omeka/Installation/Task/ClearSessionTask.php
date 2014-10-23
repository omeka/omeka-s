<?php
namespace Omeka\Installation\Task;

use Omeka\Installation\Manager;

/**
 * Task to clear identity from the session.
 */
class ClearSessionTask implements TaskInterface
{
    public function perform(Manager $manager)
    {
        $auth = $manager->getServiceLocator()->get('Omeka\AuthenticationService');
        $auth->getStorage()->clear();
    }
}
