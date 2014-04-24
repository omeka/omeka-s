<?php
namespace Omeka\Mvc;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\Mvc\MvcEvent;

class ApiAuthenticationListener implements ListenerAggregateInterface
{
    /**
     * @var array
     */
    protected $listeners = array();

    /**
     * {@inheritDoc}
     */
    public function attach(EventManagerInterface $events)
    {
        // A low priority is required to ensure that authorization happens last.
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_ROUTE,
            array($this, 'onRoute')
        );
    }

    /**
     * {@inheritDoc}
     */
    public function detach(EventManagerInterface $events)
    {
        foreach ($this->listeners as $index => $callback) {
            if ($events->detach($callback)) {
                unset($this->listeners[$index]);
            }
        }
    }

    /**
     * Authorize the current user against an API key.
     *
     * @param MvcEvent $event
     */
    public function onRoute(MvcEvent $event)
    {
        $status = $event->getApplication()->getServiceManager()
            ->get('Omeka\InstallationStatus');

        if (!$status->isApiRequest()) {
            // This is not an API request.
            return;
        }

        $key = $event->getRequest()->getQuery('key');
        if (null === $key) {
            // No key to authenticate against.
            return;
        }

        $auth = $event->getApplication()->getServiceManager()
            ->get('Omeka\AuthenticationService');
        $adapter = $auth->getAdapter()->setCredential($key);
        $auth->authenticate();
    }
}
