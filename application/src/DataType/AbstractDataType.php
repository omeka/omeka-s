<?php
namespace Omeka\DataType;

use Omeka\Api\Representation\ValueRepresentation;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

abstract class AbstractDataType implements DataTypeInterface
{
    use ServiceLocatorAwareTrait;

    public function toString(ValueRepresentation $value)
    {
        return $value->value();
    }
}
