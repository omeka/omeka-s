<?php
namespace Omeka\View\Helper;

use Omeka\Event\Event;
use Omeka\Event\FilterEvent;
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
     * Trigger a view filter event.
     *
     * @param string $name The event name
     * @param array $params The event parameters
     */
    public function filter($name, array $params = array())
    {
        $params['services'] = $this->serviceLocator;
        $event = new FilterEvent($name, $this->getView(), $params);
        $event->setArg(array());

        // Set the current controller as the event identifier.
        $controller = $this->serviceLocator->get('Application')->getMvcEvent()
            ->getRouteMatch()->getParam('controller');
        $this->events->setIdentifiers($controller);
        $this->events->trigger($event);

        if (is_array($event->getArg())) {
            return implode($event->getArg());
        }
        return $event->getArg();
    }

    public function event($name, array $params = array())
    {
        $params['services'] = $this->serviceLocator;
        $event = new Event($name, $this->getView(), $params);

        // Set the current controller as the event identifier.
        $controller = $this->serviceLocator->get('Application')->getMvcEvent()
            ->getRouteMatch()->getParam('controller');
        $this->events->setIdentifiers($controller);
        $this->events->trigger($event);
    }
}
