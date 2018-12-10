<?php
namespace Omeka\ServiceManager;

use Zend\EventManager\EventManagerAwareTrait;
use Zend\ServiceManager\AbstractPluginManager as ZendAbstractPluginManager;
use Zend\EventManager\Event;

abstract class AbstractPluginManager extends ZendAbstractPluginManager
{
    use EventManagerAwareTrait;

    /**
     * Registered invokable and factory service names.
     *
     * @var array
     */
    protected $registeredNames = [];

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
     * Set the registered names.
     *
     * @param array $config
     */
    public function configure(array $config)
    {
        parent::configure($config);
        if (isset($config['factories']) && is_array($config['factories'])) {
            $factoryKeys = array_keys($config['factories']);
            $this->registeredNames = array_merge(
                $this->registeredNames,
                array_combine($factoryKeys, $factoryKeys)
            );
        }
        if (isset($config['invokables']) && is_array($config['invokables'])) {
            $invokableKeys = array_keys($config['invokables']);
            $this->registeredNames = array_merge(
                $this->registeredNames,
                array_combine($invokableKeys, $invokableKeys)
            );
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
        $registeredNames = array_merge(
            $this->sortedNames,
            array_values(array_diff($this->registeredNames, $this->sortedNames))
        );
        $args = $this->getEventManager()->prepareArgs([
            'registered_names' => $registeredNames,
        ]);
        $this->getEventManager()->trigger('service.registered_names', $this, $args);
        return $args['registered_names'];
    }
}
