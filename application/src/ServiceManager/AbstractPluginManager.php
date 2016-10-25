<?php
namespace Omeka\ServiceManager;

use Zend\EventManager\EventManagerAwareTrait;
use Zend\ServiceManager\AbstractPluginManager as ZendAbstractPluginManager;
use Zend\EventManager\Event;

abstract class AbstractPluginManager extends ZendAbstractPluginManager
{
    use EventManagerAwareTrait;

    /**
     * Sorted array of service names. Names specified here are sorted
     * accordingly in the getRegisteredNames output. Names not specified
     * are left in their natural order.
     *
     * @var array
     */
    protected $sortedNames = [];

    public function __construct($configOrContainerInterface = null, array $v3config = [])
    {
        parent::__construct($configOrContainerInterface, $v3config);
        $this->setEventManager($configOrContainerInterface->get('EventManager'));

        if (isset($v3config['sorted_names'])) {
            $this->sortedNames = $v3config['sorted_names'];
        }
    }
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
        $aliases = $this->aliases;
        $registeredNames = array_keys($aliases);
        foreach ($this->factories as $key => $value) {
            if (!in_array($key, $aliases)) {
                $registeredNames[] = $key;
            }
        }
        $registeredNames = array_merge($this->sortedNames, array_diff($registeredNames, $this->sortedNames));
        $args = $this->getEventManager()->prepareArgs([
            'registered_names' => $registeredNames,
        ]);
        $this->getEventManager()->trigger('service.registered_names', $this, $args);
        return $args['registered_names'];
    }
}
