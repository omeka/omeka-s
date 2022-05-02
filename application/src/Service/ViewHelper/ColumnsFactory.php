<?php

namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\Columns;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ColumnsFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Columns($services);
    }
}
