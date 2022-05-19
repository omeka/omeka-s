<?php

namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\Browse;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class BrowseFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Browse(
            $services->get('Omeka\Browse'),
            $services->get('FormElementManager')
        );
    }
}
