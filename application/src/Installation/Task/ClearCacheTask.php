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
        $em = $manager->getServiceLocator()->get('Omeka\EntityManager');
        $cache = $em->geConfiguration()->getMetadataCacheImpl();

        if (!$cache) {
            return;
        }

        $cache->deleteAll();
    }
}
