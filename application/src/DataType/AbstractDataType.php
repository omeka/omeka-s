<?php
namespace Omeka\DataType;

use Zend\ServiceManager\ServiceLocatorAwareTrait;

abstract class AbstractDataType implements DataTypeInterface
{
    use ServiceLocatorAwareTrait;
}
