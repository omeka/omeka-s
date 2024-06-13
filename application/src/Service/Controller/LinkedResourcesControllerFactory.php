<?php
namespace Omeka\Service\Controller;

use Interop\Container\ContainerInterface;
use Omeka\Controller\LinkedResourcesController;
use Laminas\ServiceManager\Factory\FactoryInterface;

class LinkedResourcesControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new LinkedResourcesController;
    }
}
