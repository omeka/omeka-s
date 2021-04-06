<?php
namespace Omeka\View\Helper;

use Laminas\EventManager\EventManagerInterface;
use Laminas\Mvc\Controller\PluginManager as ControllerPluginManager;
use Laminas\View\Helper\AbstractHelper;
use Laminas\EventManager\Event;

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
     * @param array $ids The identifiers to which the events are attached
     */
    public function __invoke($name, array $params = [], $filter = false, array $ids = null)
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
        if ($filter) {
            $params = $this->events->prepareArgs($params);
        }
        $event = new Event($name, $this->getView(), $params);
        $this->events->setIdentifiers($ids ?: [$routeMatch->getParam('controller')]);
        $this->events->triggerEvent($event);
        if ($filter) {
            return $params;
        }
    }
}
