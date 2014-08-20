<?php
namespace Omeka;

use Omeka\Event\Event;
use Omeka\Module\AbstractModule;
use Omeka\View\Helper\Api;
use Omeka\View\Helper\AssetUrl;
use Omeka\View\Helper\Media;
use Omeka\View\Helper\Pagination;
use Zend\EventManager\SharedEventManagerInterface;
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

        // Inject the service manager into view helpers that need it.
        $viewHelperManager = $serviceManager->get('ViewHelperManager');
        $viewHelperManager->setFactory('Api',
            function ($helperPluginManager) use ($serviceManager) {
                return new Api($serviceManager);
            });
        $viewHelperManager->setFactory('AssetUrl',
            function ($helperPluginManager) use ($serviceManager) {
                return new AssetUrl($serviceManager);
            });
        $viewHelperManager->setFactory('Media',
            function ($helperPluginManager) use ($serviceManager) {
                return new Media($serviceManager);
            });
        $viewHelperManager->setFactory('Pagination',
            function ($helperPluginManager) use ($serviceManager) {
                return new Pagination($serviceManager);
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

    public function attachListeners(
        SharedEventManagerInterface $sharedEventManager,
        SharedEventManagerInterface $filterManager
    ) {}
}
