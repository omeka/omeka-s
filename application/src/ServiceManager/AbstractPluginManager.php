<?php
namespace Omeka\ServiceManager;

use Laminas\EventManager\EventManagerAwareTrait;
use Laminas\ServiceManager\AbstractPluginManager as ZendAbstractPluginManager;
use Laminas\EventManager\Event;

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
        $this->translator = $configOrContainerInterface->get('MvcTranslator');

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
     * @param bool $sortAlpha
     * @return array
     */
    public function getRegisteredNames($sortAlpha = false)
    {
        $registeredNames = $this->registeredNames;
        if ($sortAlpha) {
            // Sort services that implement SortableInterface alphabetically,
            // followed by other services, which will remain in their natural
            // order.
            $sortedStrings = [];
            $unsortedNames = [];
            foreach ($registeredNames as $registeredName) {
                $service = $this->get($registeredName);
                if ($service instanceof SortableInterface) {
                    $sortableString = $this->translator->translate($service->getSortableString());
                    $sortedStrings[$registeredName] = $sortableString;
                } else {
                    $unsortedNames[] = $registeredName;
                }
            }
            // Sort strings alphabetically.
            if (extension_loaded('intl')) {
                $collator = new \Collator('root');
                $collator->asort($sortedStrings);
            } else {
                natcasesort($sortedStrings);
            }
            // Sorted names come before unsorted names.
            $registeredNames = array_merge(array_keys($sortedStrings), $unsortedNames);
        }
        // Reorder names according to sorted_names configuration.
        $registeredNames = array_merge(
            $this->sortedNames,
            array_values(array_diff($registeredNames, $this->sortedNames))
        );
        // Filter names through the service.registered_names event.
        $args = $this->getEventManager()->prepareArgs([
            'registered_names' => $registeredNames,
        ]);
        $this->getEventManager()->trigger('service.registered_names', $this, $args);
        return $args['registered_names'];
    }
}
