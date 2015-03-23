<?php
namespace Omeka;

use Omeka\Event\Event;
use Omeka\Module\AbstractModule;
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
    const VERSION = '0.1.20';

    /**
     * @var array View helpers that need service manager injection
     */
    protected $viewHelpers = array(
        'api'        => 'Omeka\View\Helper\Api',
        'assetUrl'   => 'Omeka\View\Helper\AssetUrl',
        'i18n'       => 'Omeka\View\Helper\I18n',
        'media'      => 'Omeka\View\Helper\Media',
        'nav'        => 'Omeka\View\Helper\Nav',
        'pagination' => 'Omeka\View\Helper\Pagination',
        'trigger'    => 'Omeka\View\Helper\Trigger',
    );

    /**
     * {@inheritDoc}
     */
    public function onBootstrap(MvcEvent $event)
    {
        parent::onBootstrap($event);

        $serviceManager = $this->getServiceLocator();
        $viewHelperManager = $serviceManager->get('ViewHelperManager');

        // Inject the service manager into view helpers that need it.
        foreach ($this->viewHelpers as $helperName => $helperClass) {
            $viewHelperManager->setFactory($helperName,
                function ($helperPluginManager) use ($helperClass, $serviceManager) {
                    return new $helperClass($serviceManager);
                });
        }

        // Set the ACL to navigation.
        $acl = $serviceManager->get('Omeka\Acl');
        $navigation = $viewHelperManager->get('Navigation');
        $navigation->setAcl($acl);
    }

    /**
     * {@inheritDoc}
     */
    public function getConfig()
    {
        return array_merge(
            include __DIR__ . '/config/module.config.php',
            include __DIR__ . '/config/routes.config.php',
            include __DIR__ . '/config/navigation.config.php'
        );
    }

    /**
     * {@inheritDoc}
     */
    public function attachListeners(
        SharedEventManagerInterface $sharedEventManager,
        SharedEventManagerInterface $filterManager
    ) {
        $sharedEventManager->attach(
            'Zend\View\Helper\Navigation\AbstractHelper',
            'isAllowed',
            array($this, 'navigationPageIsAllowed')
        );
    }

    /**
     * Determine whether a navigation page is allowed.
     *
     * @param BaseEvent $event
     * @return bool
     */
    public function navigationPageIsAllowed(BaseEvent $event)
    {
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
    }
}
