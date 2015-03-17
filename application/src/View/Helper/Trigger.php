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
     * Trigger a view event.
     *
     * @param string $name The event name
     * @param array $params The event parameters
     */
    public function __invoke($name, array $params = array())
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
