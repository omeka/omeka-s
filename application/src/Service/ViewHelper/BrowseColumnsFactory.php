<?php

namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\BrowseColumns;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class BrowseColumnsFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new BrowseColumns($services);
    }
}
