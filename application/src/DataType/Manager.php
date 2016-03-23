<?php
namespace Omeka\DataType;

use Omeka\Api\Exception;
use Omeka\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\Exception\ServiceNotFoundException;

class Manager extends AbstractPluginManager
{
    /**
     * {@inheritDoc}
     */
    protected $canonicalNamesReplacements = [];

    /**
     * {@inheritDoc}
     */
    public function get($name, $options = [],
        $usePeeringServiceManagers = true
    ) {
        try {
            $instance = parent::get($name, $options, $usePeeringServiceManagers);
        } catch (ServiceNotFoundException $e) {
            // Use "literal" as the fallback data type.
            $instance = $this->get('literal');
        }
        return $instance;
    }

    /**
     * {@inheritDoc}
     */
    public function validatePlugin($plugin)
    {
        if (!is_subclass_of($plugin, 'Omeka\DataType\DataTypeInterface')) {
            throw new Exception\InvalidAdapterException(sprintf(
                'The media ingester class "%1$s" does not implement Omeka\DataType\DataTypeInterface.',
                get_class($plugin)
            ));
        }
    }
}
