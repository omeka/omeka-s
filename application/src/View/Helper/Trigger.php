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
    public function __invoke($name, array $params = [], $filter = false, ?array $ids = null)
    {
        $controller = $this->controllerPluginManager->getController();
        $routeMatch = $controller ? $controller->getEvent()->getRouteMatch() : null;
        if ($filter) {
            $params = $this->events->prepareArgs($params);
        }
        $event = new Event($name, $this->getView(), $params);
        // Fall back to the error identifier when no route matched (404, error
        // page), so modules attached to "view.layout" can still observe event.
        $isError = !$routeMatch;
        $this->events->setIdentifiers($ids ?: [$isError ? 'Omeka\Controller\Error' : $routeMatch->getParam('controller')]);
        // Avoid cascaded error or blank page hiding original failure above.
        // Nevertheless, add a second message in that case and error_log().
        if ($isError) {
            try {
                $this->events->triggerEvent($event);
            } catch (\Throwable $e) {
                try {
                    $this->getView()->getHelperPluginManager()->getServiceLocator()
                        ->get('Omeka\Logger')->err(
                            'Listener exception while triggering "{name}" on error page: {message} in {file}:{line}',
                            ['name' => $name, 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]
                        );
                } catch (\Throwable $loggerError) {
                    error_log(sprintf(
                        'Omeka Trigger: listener exception while triggering "%s" on error page: %s in %s:%d',
                        $name,
                        $e->getMessage(),
                        $e->getFile(),
                        $e->getLine()
                    ));
                }
            }
        } else {
            $this->events->triggerEvent($event);
        }
        if ($filter) {
            return $params;
        }
    }
}
