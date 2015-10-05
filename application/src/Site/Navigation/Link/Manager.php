<?php
namespace Omeka\Site\Navigation\Link;

use Omeka\Api\Exception;
use Omeka\Site\Navigation\Link\Fallback;
use Omeka\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\ConfigInterface;
use Zend\ServiceManager\Exception\ServiceNotFoundException;

class Manager extends AbstractPluginManager
{
    /**
     * {@inheritDoc}
     */
    protected $canonicalNamesReplacements = array();

    /**
     * {@inheritDoc}
     */
    public function __construct(ConfigInterface $configuration = null)
    {
        parent::__construct($configuration);
        $this->addInitializer(function($instance, $serviceLocator) {
            $instance->setServiceLocator($serviceLocator->getServiceLocator());
        }, false);
    }

    /**
     * {@inheritDoc}
     */
    public function get($name, $options = array(),
        $usePeeringServiceManagers = true
    ){
        try {
            $instance = parent::get($name, $options, $usePeeringServiceManagers);
        } catch (ServiceNotFoundException $e) {
            $instance = new Fallback($name);
            $instance->setServiceLocator($this->getServiceLocator());
        }
        return $instance;
    }

    /**
     * {@inheritDoc}
     */
    public function validatePlugin($plugin)
    {
        if (!is_subclass_of($plugin, 'Omeka\Site\Navigation\Link\LinkInterface')) {
            throw new Exception\InvalidAdapterException(sprintf(
                'The navigation link class "%1$s" does not implement Omeka\Site\Navigation\Link\LinkInterface.',
                get_class($plugin)
            ));
        }
    }
}
