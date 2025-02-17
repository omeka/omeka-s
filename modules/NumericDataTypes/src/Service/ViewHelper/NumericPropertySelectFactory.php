<?php
namespace NumericDataTypes\Service\ViewHelper;

use NumericDataTypes\View\Helper\NumericPropertySelect;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class NumericPropertySelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new NumericPropertySelect($services->get('FormElementManager'));
    }
}
