<?php
namespace Omeka\View\Helper;

use Omeka\Event\Event;
use Zend\EventManager\EventManagerInterface;
use Zend\Mvc\Application;
use Zend\View\Helper\AbstractHelper;

class Trigger extends AbstractHelper
{
    /**
     * @var EventManagerInterface
     */
    protected $events;

    /**
     * @var Application
     */
    protected $application;

    /**
     * Construct the helper.
     *
     * @param EventManagerInterface $eventManager
     * @param Application $application
     */
    public function __construct(EventManagerInterface $eventManager, Application $application)
    {
        $this->events = $eventManager;
        $this->application = $application;
    }

    /**
     * Trigger a view event.
     *
     * @param string $name The event name
     * @param array $params The event parameters
     * @param bool $filter Filter and return parameters?
     */
    public function __invoke($name, array $params = [], $filter = false)
    {
        $routeMatch = $this->application->getMvcEvent()->getRouteMatch();
        if (!$routeMatch) {
            // Without a route match this request is 404. No need to trigger.
            return;
        }

        // Set the event, using the current controller as the event identifier.
        if ($filter) {
            $params = $this->events->prepareArgs($params);
        }
        $event = new Event($name, $this->getView(), $params);
        $this->events->setIdentifiers($routeMatch->getParam('controller'));
        $this->events->trigger($event);
        if ($filter) {
            return $params;
        }
    }
}
