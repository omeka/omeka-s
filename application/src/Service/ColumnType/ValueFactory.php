<?php
namespace Omeka\Service\ColumnType;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Omeka\ColumnType\Value;

class ValueFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Value($services->get('FormElementManager'), $services->get('Omeka\ApiManager'));
    }
}
