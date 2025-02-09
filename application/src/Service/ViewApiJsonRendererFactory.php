<?php

namespace Omeka\Service;

use Omeka\View\Renderer\ApiJsonRenderer;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ViewApiJsonRendererFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $eventManager = $serviceLocator->get('EventManager');
        $jsonRenderer = new ApiJsonRenderer($eventManager);
        return $jsonRenderer;
    }
}
