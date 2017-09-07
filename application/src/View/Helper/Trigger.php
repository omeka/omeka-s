<?php
namespace Omeka\View\Helper;

use Zend\EventManager\EventManagerInterface;
use Zend\Mvc\Controller\PluginManager as ControllerPluginManager;
use Zend\View\Helper\AbstractHelper;
use Zend\EventManager\Event;

/**
 * View helper for triggering a view event.
 */
class Trigger extends AbstractHelper
{
    /**
     * @var EventManagerInterface
     */
    protected $events;

    /**
     * @var ControllerPluginManager
     */
    protected $controllerPluginManager;

    /**
     * Construct the helper.
     *
     * @param EventManagerInterface $eventManager
     * @param ControllerPluginManager $controllerPluginManager
     */
    public function __construct(EventManagerInterface $eventManager, ControllerPluginManager $controllerPluginManager)
    {
        $this->events = $eventManager;
        $this->controllerPluginManager = $controllerPluginManager;
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
        $controller = $this->controllerPluginManager->getController();
        if (!$controller) {
            return $filter ? $params : null;
        }
        $routeMatch = $controller->getEvent()->getRouteMatch();
        if (!$routeMatch) {
            // Without a route match this request is 404. No need to trigger.
            return $filter ? $params : null;
        }

        // Set the event, using the current controller as the event identifier.
        if ($filter) {
            $params = $this->events->prepareArgs($params);
        }
        $event = new Event($name, $this->getView(), $params);
        $this->events->setIdentifiers([$routeMatch->getParam('controller')]);
        $this->events->triggerEvent($event);
        if ($filter) {
            return $params;
        }
    }
}
