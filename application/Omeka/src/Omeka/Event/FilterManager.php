<?php
namespace Omeka\Event;

use Zend\EventManager\SharedEventManagerInterface;
use Zend\EventManager\StaticEventManager;
use Zend\Stdlib\CallbackHandler;

class FilterManager implements SharedEventManagerInterface
{
    /**
     * {@inheritDoc}
     */
    public function getListeners($id, $event)
    {
        StaticEventManager::getInstance()->getListeners($id, $event);
    }

    /**
     * {@inheritDoc}
     */
    public function attach($id, $event, $callback, $priority = 1)
    {
        StaticEventManager::getInstance()->attach(
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
            $priority);
    }

    /**
     * {@inheritDoc}
     */
    public function detach($id, CallbackHandler $listener)
    {
        StaticEventManager::getInstance()->detach($id, $listener);
    }

    /**
     * {@inheritDoc}
     */
    public function getEvents($id)
    {
        StaticEventManager::getInstance()->getEvents($id);
    }

    /**
     * {@inheritDoc}
     */
    public function clearListeners($id, $event = null)
    {
        StaticEventManager::getInstance()->clearListeners($id, $event);
    }
}
