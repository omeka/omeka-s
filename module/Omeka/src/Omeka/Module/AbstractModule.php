<?php
namespace Omeka\Module;

use Omeka\Event\FilterEvent;
use Zend\EventManager\EventInterface;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\EventManager\SharedListenerAggregateInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Abstract Omeka module.
 */
class AbstractModule implements
    ConfigProviderInterface,
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
     * @var SharedEventManagerInterface
     */
    protected $events;

    /**
     * {@inheritDoc}
     */
    public function getConfig()
    {
        throw new \Exception('Module classes must return configuration.');
    }

    /**
     * Set the service manager and shared event manager.
     *
     * {@inheritDoc}
     */
    public function onBootstrap(EventInterface $event)
    {
        $this->services = $event->getApplication()->getServiceManager();
        $this->events = $event->getApplication()->getEventManager()
            ->getSharedManager();
        $this->events->attachAggregate($this);
    }

    /**
     * Attach module-specific events and filters.
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
     * Call this in self::attachShared().
     *
     * @see SharedEventManager::attach()
     */
    public function attachEvent($id, $event, $callback, $priority = 1)
    {
        $this->listeners[] = $this->events->attach($id, $event, $callback, $priority);
    }

    /**
     * Attach a module-specific filter listener.
     *
     * Call this in self::attachShared().
     *
     * @see SharedEventManager::attach()
     */
    public function attachFilter($id, $event, $callback, $priority = 1) {
        $this->listeners[] = $this->events->attach(
            $id, $event,
            function($e) use ($callback) {
                if (!$e instanceof FilterEvent) {
                    return;
                }
                $e->setArg(call_user_func($callback, $e->getArg(), $e));
            },
            $priority
        );
    }

}
