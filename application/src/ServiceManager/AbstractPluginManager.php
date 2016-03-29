<?php
namespace Omeka\ServiceManager;

use Omeka\Event\Event;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\ServiceManager\AbstractPluginManager as ZendAbstractPluginManager;

abstract class AbstractPluginManager extends ZendAbstractPluginManager
{
    use EventManagerAwareTrait;

    /**
     * Get registered names.
     *
     * An alternative to parent::getCanonicalNames(). Returns only the names
     * that are registered in configuration as invokable classes and factories.
     * The list many be modified during the service.registered_names event.
     *
     * @return array
     */
    public function getRegisteredNames()
    {
        $services = $this->getRegisteredServices();
        $args = $this->getEventManager()->prepareArgs([
            'registered_names' => array_merge($services['invokableClasses'], $services['factories']),
        ]);
        $this->getEventManager()->trigger(Event::SERVICE_REGISTERED_NAMES, $this, $args);
        return $args['registered_names'];
    }
}
