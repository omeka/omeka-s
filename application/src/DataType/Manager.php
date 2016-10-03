<?php
namespace Omeka\DataType;

use Omeka\Entity\Value;
use Omeka\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\Exception\ServiceNotFoundException;

class Manager extends AbstractPluginManager
{
    protected $autoAddInvokableClass = false;

    protected $instanceOf = DataTypeInterface::class;

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
            $instance = $this->get($dataType);
        } catch (ServiceNotFoundException $e) {
            $instance = $this->get($dataTypeFallback);
        }
        return $instance;
    }
}
