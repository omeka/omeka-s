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

    public function getForExtract(Value $value)
    {
        $dataType = $value->getType();
        $dataTypeFallback = 'literal';
        if (is_string($value->getUri())) {
            $dataTypeFallback = 'uri';
        } elseif ($value->getValueResource()) {
            $dataTypeFallback = 'resource';
        }
        try {
            $instance = parent::get($dataType);
        } catch (ServiceNotFoundException $e) {
            $instance = $this->get($dataTypeFallback);
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
