<?php
namespace Omeka\View\Helper;

use Omeka\Event\Event;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Helper\AbstractHelper;

class Trigger extends AbstractHelper
{
    /**
     * Construct the helper.
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
        $this->events = $serviceLocator->get('EventManager');
    }

    /**
     * Trigger a view event.
     *
     * @param string $name The event name
     * @param array $params The event parameters
     */
    public function __invoke($name, array $params = [])
    {
        $routeMatch = $this->serviceLocator->get('Application')
            ->getMvcEvent()->getRouteMatch();
        if (!$routeMatch) {
            // Without a route match this request is 404. No need to trigger.
            return;
        }

        // Set the event, using the current controller as the event identifier.
        $params['services'] = $this->serviceLocator;
        $event = new Event($name, $this->getView(), $params);
        $this->events->setIdentifiers($routeMatch->getParam('controller'));
        $this->events->trigger($event);
    }
}
