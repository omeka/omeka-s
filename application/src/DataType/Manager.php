<?php
namespace Omeka\DataType;

use Omeka\Api\Exception;
use Omeka\Entity\Value;
use Omeka\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\Exception\ServiceNotFoundException;

class Manager extends AbstractPluginManager
{
    /**
     * {@inheritDoc}
     */
    protected $canonicalNamesReplacements = [];

    /**
     * Get the data type service.
     *
     * This method accepts one of three things for the first argument:
     *
     *   - a JSON-LD value object array, for hydration;
     *   - a Value entity, for extraction;
     *   - or the name of the data type, for all other uses.
     *
     * A JSON-LD value object and Value entity are needed to intelligently
     * derive a fallback data type in case a passed one isn't registered.
     *
     * @param string|array|Value
     * {@inheritDoc}
     */
    public function get($name, $options = [],
        $usePeeringServiceManagers = true
    ) {
        $fallbackDataType = 'literal';

        if ($name instanceof Value) {
            // Derive data type and fallback data type from a Value entity.
            $dataType = $name->getType();
            if (is_string($name->getUri())) {
                $fallbackDataType = 'uri';
            } elseif ($name->getValueResource()) {
                $fallbackDataType = 'resource';
            }

        } elseif (is_array($name) && isset($name['type'])) {
            // Derive data type and fallback data type from an array representing
            // a JSON-LD value object.
            $dataType = $name['type'];
            if (isset($name['@id'])) {
                $fallbackDataType = 'uri';
            } elseif (isset($name['value_resource_id'])) {
                $fallbackDataType = 'resource';
            }

        } else {
            $dataType = $name;
        }

        try {
            $instance = parent::get($dataType, $options, $usePeeringServiceManagers);
        } catch (ServiceNotFoundException $e) {
            // Get the fallback data type.
            $instance = $this->get($fallbackDataType);
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
