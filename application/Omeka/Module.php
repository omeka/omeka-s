<?php
namespace Omeka;

use Omeka\Event\Event;
use Omeka\Module\AbstractModule;
use Omeka\View\Helper\Api;
use Omeka\View\Helper\AssetUrl;
use Omeka\View\Helper\Media;
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
    ) {
        $sharedEventManager->attach(
            'Omeka\Model\Entity\Resource',
            Event::ENTITY_PERSIST_PRE,
            array($this, 'setRdfsResourceAsDefault')
        );
    }

    /**
     * Set rdfs:Resource as the default resource class for all Omeka resources.
     *
     * @param $event
     */
    public function setRdfsResourceAsDefault($event)
    {
        $resourceEntity = $event->getTarget();
        if (null === $resourceEntity->getResourceClass()) {
            // get the rdfs:Resource class and set it to this resource
            $rdfsResource = $this->getServiceLocator()
                ->get('Omeka\EntityManager')
                ->getRepository('Omeka\Model\Entity\ResourceClass')
                ->getRdfsResource();
            $resourceEntity->setResourceClass($rdfsResource);
        }
    }
}
