<?php
namespace Omeka\Module;

use Omeka\Event\FilterEvent;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\EventManager\SharedListenerAggregateInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Abstract Omeka module.
 */
class AbstractModule implements
    ConfigProviderInterface,
    ServiceLocatorAwareInterface,
    SharedListenerAggregateInterface
{
    /**
     * @var array
     */
    protected $listeners = array();

    /**
     * @var ServiceLocatorInterface
     */
    protected $services;

    /**
     * Bootstrap the module.
     *
     * Be sure to call parent::onBootstrap($event) first when overriding this
     * method.
     */
    public function onBootstrap(MvcEvent $event)
    {
        $this->setServiceLocator($event->getApplication()->getServiceManager());
        $this->getServiceLocator()->get('SharedEventManager')->attachAggregate($this);
    }

    /**
     * Return module-specific configuration.
     *
     * {@inheritDoc}
     */
    public function getConfig()
    {}

    /**
     * Attach module-specific event and filter listeners.
     *
     * {@inheritDoc}
     */
    public function attachShared(SharedEventManagerInterface $events)
    {}

    /**
     * {@inheritDoc}
     */
    public function detachShared(SharedEventManagerInterface $events)
    {
        foreach ($this->listeners as $index => $callback) {
            if ($events->detach($callback)) {
                unset($this->listeners[$index]);
            }
        }
    }

    /**
     * Attach a module-specific event listener.
     *
     * Call this in {@link self::attachShared()}, as in this example:
     *
     * <code>
     * $this->attach(
     *     'Omeka\Identifier',
     *     'event',
     *     array($this, 'myCallback')
     * );
     * </code>
     *
     * The callback receives a {@link \Zend\EventManager\EventInterface} object
     * as its only parameter.
     *
     * @see SharedEventManager::attach()
     * @param string|array $id Identifier(s) for event emitting component(s)
     * @param string $event
     * @param callable $callback PHP Callback
     * @param int $priority Priority at which listener should execute
     * @return CallbackHandler|array
     */
    public function attach($id, $event, $callback, $priority = 1)
    {
        $this->listeners[] = $this->getServiceLocator()
            ->get('SharedEventManager')->attach(
                $id, $event, $callback, $priority
            );
    }

    /**
     * Attach a module-specific filter event listener.
     *
     * Call this in {@link self::attachShared()}, as in this example:
     *
     * <code>
     * $this->attachFilter(
     *     'Omeka\Identifier',
     *     'filterEvent',
     *     array($this, 'myFilterCallback')
     * );
     * </code>
     *
     * The callback receives the argument to filter as the first parameter and a
     * {@link \Zend\EventManager\EventInterface} object as the second. Callbacks
     * should filter the argument and return it. This ignores events that aren't
     * specifically declared as filter events.
     *
     * @see SharedEventManager::attach()
     * @param string|array $id
     * @param string $event
     * @param callable $callback
     * @param int $priority
     * @return CallbackHandler|array
     */
    public function attachFilter($id, $event, $callback, $priority = 1) {
        $this->listeners[] = $this->getServiceLocator()
            ->get('SharedEventManager')->attach(
                $id, $event,
                function($e) use ($callback) {
                    if (!$e instanceof FilterEvent) {
                        // Ignore non-filter events.
                        return;
                    }
                    // Set the new argument as the response of the user-defined
                    // callback.
                    $e->setArg(call_user_func($callback, $e->getArg(), $e));
                },
                $priority
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
}
