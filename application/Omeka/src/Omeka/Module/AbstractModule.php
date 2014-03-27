<?php
namespace Omeka\Module;

use Omeka\Event\FilterEvent;
use ReflectionClass;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Abstract Omeka module.
 */
abstract class AbstractModule implements
    ConfigProviderInterface,
    ServiceLocatorAwareInterface,
    EventManagerAwareInterface
{
    /**
     * @var ServiceLocatorInterface
     */
    protected $services;

    /**
     * @var EventManagerInterface
     */
    protected $events;

    /**
     * Bootstrap the module.
     *
     * Call parent::onBootstrap($event) first when overriding this method.
     *
     * @param MvcEvent $event
     */
    public function onBootstrap(MvcEvent $event)
    {
        $this->setServiceLocator($event->getApplication()->getServiceManager());
        $this->attachListeners(
            $this->getServiceLocator()->get('SharedEventManager'),
            $this->getServiceLocator()->get('Omeka\FilterManager')
        );
    }

    /**
     * Install this module.
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function install(ServiceLocatorInterface $serviceLocator)
    {}

    /**
     * Uninstall this module.
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {}

    /**
     * Upgrade this module.
     *
     * @param string $oldVersion
     * @param string $newVersion
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function upgrade($oldVersion, $newVersion,
        ServiceLocatorInterface $serviceLocator
    ) {}

    /**
     * Attach shared event and filter listeners.
     *
     * Attach listeners to the $sharedEventManager for shared events:
     *
     * <code>
     * $sharedEventManager->attach(
     *     'Omeka\Identifier',
     *     'shared_event_name',
     *     array($this, 'mySharedEventCallback')
     * );
     * </code>
     *
     * The shared event callbacks receive a
     * {@link \Zend\EventManager\EventInterface} object as its only parameter.
     *
     * Attach listeners to the $filterManager for filters:
     *
     * <code>
     * $filterManager->attach(
     *     'Omeka\Identifier',
     *     'filter_name',
     *     array($this, 'myFilterCallback')
     * );
     * </code>
     *
     * The filter callback receives the argument to filter as the first
     * parameter and a {@link \Zend\EventManager\EventInterface} object as the
     * second. Callbacks should filter the argument and return it. This ignores
     * events that aren't specifically declared as filter events.
     *
     * @param SharedEventManagerInterface $sharedEventManager
     * @param SharedEventManagerInterface $filterManager
     */
    public function attachListeners(
        SharedEventManagerInterface $sharedEventManager,
        SharedEventManagerInterface $filterManager
    ) {}

    /**
     * Return module-specific configuration.
     *
     * {@inheritDoc}
     */
    public function getConfig()
    {}

    /**
     * {@inheritDoc}
     */
    public function getAutoloaderConfig()
    {
        $classInfo = new ReflectionClass($this);
        $namespace = $classInfo->getNamespaceName();

        // Omeka is already registered via Composer.
        if ('Omeka' == $namespace) {
            return;
        }

        $autoloadPath = sprintf('%1$s/module/%2$s/src/%2$s', OMEKA_PATH, $namespace);
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    $namespace => $autoloadPath,
                ),
            ),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->services = $serviceLocator;
    }

    /**
     * {@inheritDoc}
     */
    public function getServiceLocator()
    {
        return $this->services;
    }

    /**
     * {@inheritDoc}
     */
    public function setEventManager(EventManagerInterface $events)
    {
        $events->setIdentifiers(get_called_class());
        $this->events = $events;
    }

    /**
     * {@inheritDoc}
     */
    public function getEventManager()
    {
        if (null === $this->events) {
            $this->setEventManager($this->getServiceLocator()->get('EventManager'));
        }
        return $this->events;
    }
}
