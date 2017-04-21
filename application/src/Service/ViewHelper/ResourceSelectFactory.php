<?php
namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\ResourceSelect;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ResourceSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ResourceSelect($services->get('FormElementManager'));
    }
}
