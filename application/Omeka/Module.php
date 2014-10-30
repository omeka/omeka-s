<?php
namespace Omeka;

use Omeka\Event\Event;
use Omeka\Module\AbstractModule;
use Omeka\View\Helper\Api;
use Omeka\View\Helper\AssetUrl;
use Omeka\View\Helper\I18n;
use Omeka\View\Helper\Media;
use Omeka\View\Helper\Pagination;
use Zend\EventManager\Event as BaseEvent;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\Mvc\MvcEvent;

/**
 * The Omeka module.
 */
class Module extends AbstractModule
{
    /**
     * This Omeka version.
     */
    const VERSION = '0.1.0';

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
        $viewHelperManager->setFactory('I18n',
            function ($helperPluginManager) use ($serviceManager) {
                return new I18n($serviceManager);
            });

        // Set the ACL to navigation.
        $acl = $serviceManager->get('Omeka\Acl');
        $navigation = $viewHelperManager->get('Navigation');
        $navigation->setAcl($acl);
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function attachListeners(
        SharedEventManagerInterface $sharedEventManager,
        SharedEventManagerInterface $filterManager
    ) {
        $sharedEventManager->attach('Zend\View\Helper\Navigation\AbstractHelper',
            'isAllowed', function (BaseEvent $event) {
                $accepted = true;
                $params   = $event->getParams();
                $acl      = $params['acl'];
                $page     = $params['page'];

                if (!$acl) {
                    return $accepted;
                }

                $resource  = $page->getResource();
                $privilege = $page->getPrivilege();

                if ($resource || $privilege) {
                    $accepted = $acl->hasResource($resource)
                                && $acl->userIsAllowed($resource, $privilege);
                }

                $event->stopPropagation();
                return $accepted;
        });
    }
}
