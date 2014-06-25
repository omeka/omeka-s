<?php
namespace Omeka;

use Omeka\Module\AbstractModule;
use Omeka\View\Helper\Api;
use Omeka\View\Helper\AssetUrl;
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

        // Inject services into view helpers that need them.
        $viewHelperManager = $serviceManager->get('ViewHelperManager');
        $viewHelperManager->setFactory('Api',
            function ($helperPluginManager) use ($serviceManager) {
                return new Api($serviceManager->get('Omeka\ApiManager'));
            });
        $viewHelperManager->setFactory('AssetUrl',
            function ($helperPluginManager) use ($serviceManager) {
                return new AssetUrl($serviceManager->get('Omeka\ModuleManager'));
            });

        // Set the ACL to navigation.
        $acl = $serviceManager->get('Omeka\Acl');
        $navigation = $viewHelperManager->get('Navigation');
        $navigation->setAcl($acl)->setRole('current_user');
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }
}
