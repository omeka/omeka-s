<?php
namespace Omeka;

use Omeka\Module\AbstractModule;
use Omeka\View\Helper\Api;
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

        // Inject the API manager into the Api view helper.
        $serviceManager->get('viewhelpermanager')
            ->setFactory('Api', function ($helperPluginManager) use ($serviceManager) {
                return new Api($serviceManager->get('Omeka\ApiManager'));
            });
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }
}
