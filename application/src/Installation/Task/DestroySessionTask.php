<?php
namespace Omeka\Installation\Task;

use Omeka\Installation\Installer;
use Zend\Session\Container;

/**
 * Task to destroy the session.
 */
class DestroySessionTask implements TaskInterface
{
    public function perform(Installer $installer)
    {
        $sessionManager = Container::getDefaultManager();
        $sessionManager->start();
        // Must explicitly clear storage since the session manager will
        // repopulate the session with old storage data.
        $sessionManager->destroy(['clear_storage' => true]);
    }
}
