<?php
namespace Omeka;

use Omeka\Module\AbstractModule;
use Omeka\View\Helper\Api;
use Zend\ModuleManager\ModuleManager;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;

/**
 * The Omeka module.
 */
class Module extends AbstractModule
{
    public function onBootstrap(MvcEvent $event)
    {
        parent::onBootstrap($event);

        $serviceManager = $this->getServiceLocator();
        $eventManager = $event->getApplication()->getEventManager();

        // Enable the /:controller/:action route using __NAMESPACE__.
        $moduleRouteListener = new ModuleRouteListener;
        $moduleRouteListener->attach($eventManager);

        // Inject the API manager into the Api view helper.
        $serviceManager->get('viewhelpermanager')
            ->setFactory('Api', function ($helperPluginManager) use ($serviceManager) {
                return new Api($serviceManager->get('ApiManager'));
            });
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }
}
