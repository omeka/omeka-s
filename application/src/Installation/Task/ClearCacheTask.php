<?php
namespace Omeka\Installation\Task;

use Omeka\Installation\Installer;

/**
 * Task to clear Doctrine's metadata cache.
 */
class ClearCacheTask implements TaskInterface
{
    public function perform(Installer $installer)
    {
        $em = $installer->getServiceLocator()->get('Omeka\EntityManager');
        $cache = $em->getConfiguration()->getMetadataCacheImpl();

        if (!$cache) {
            return;
        }

        $cache->deleteAll();
    }
}
