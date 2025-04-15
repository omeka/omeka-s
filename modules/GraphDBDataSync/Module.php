<?php
namespace GraphDBDataSync;

use Omeka\Module\AbstractModule;
use Laminas\Mvc\MvcEvent;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap(MvcEvent $event)
{
    $services = $event->getApplication()->getServiceManager();
    $acl = $services->get('Omeka\Acl');

    $resourceId = 'GraphDBDataSync\Controller\Admin\Index';
    if (!$acl->hasResource($resourceId)) {
        $acl->addResource($resourceId);
    }

    $acl->allow(
        null, // All roles, or use 'admin' if you want to restrict it
        $resourceId,
        ['browse', 'edit', 'sync']
    );
}

}
