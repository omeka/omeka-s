<?php
namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\PropertySelect;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class PropertySelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new PropertySelect($services->get('FormElementManager'));
    }
}
