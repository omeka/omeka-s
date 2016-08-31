<?php

namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\ResourceClassSelect;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ResourceClassSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ResourceClassSelect($services->get('FormElementManager'));
    }
}
