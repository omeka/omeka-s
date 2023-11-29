<?php

namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\PageLayout;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class PageLayoutFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new PageLayout($services->get('EventManager'));
    }
}
