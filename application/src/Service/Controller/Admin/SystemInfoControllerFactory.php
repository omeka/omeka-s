<?php
namespace Omeka\Service\Controller\Admin;

use Interop\Container\ContainerInterface;
use Omeka\Controller\Admin\SystemInfoController;
use Laminas\ServiceManager\Factory\FactoryInterface;

class SystemInfoControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new SystemInfoController(
            $services->get('Omeka\Connection'),
            $services->get('Config'),
            $services->get('Omeka\Cli'),
            $services->get('Omeka\ModuleManager')
        );
    }
}
