<?php
namespace Omeka\Api\Adapter;

use Omeka\Api\Exception;
use Zend\ServiceManager\AbstractPluginManager;

class Manager extends AbstractPluginManager
{
    /**
     * Do not replace strings during canonicalization.
     *
     * This prevents distinct yet similarly named resources from referencing the
     * same adapter instance.
     *
     * {@inheritDoc}
     */
    protected $canonicalNamesReplacements = [];

    /**
     * {@inheritDoc}
     */
    public function __construct($configOrContainerInstance = null, array $v3config = [])
    {
        parent::__construct($configOrContainerInstance, $v3config);
        $this->addInitializer(function ($instance, $serviceLocator) {
            $instance->setServiceLocator($serviceLocator->getServiceLocator());
        }, false);
    }

    /**
     * {@inheritDoc}
     */
    public function validatePlugin($plugin)
    {
        if (!is_subclass_of($plugin, 'Omeka\Api\Adapter\AdapterInterface')) {
            throw new Exception\InvalidAdapterException(sprintf(
                'The adapter class "%1$s" does not implement Omeka\Api\Adapter\AdapterInterface.',
                get_class($plugin)
            ));
        }
    }
}
