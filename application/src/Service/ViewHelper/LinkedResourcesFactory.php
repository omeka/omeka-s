<?php

namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\LinkedResources;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class LinkedResourcesFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new LinkedResources;
    }
}
