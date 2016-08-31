<?php
namespace Omeka\Service\Controller\Admin;

use Interop\Container\ContainerInterface;
use Omeka\Controller\Admin\ModuleController;
use Zend\ServiceManager\Factory\FactoryInterface;

class ModuleControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ModuleController(
            $services->get('ViewRenderer'),
            $services->get('ModuleManager'),
            $services->get('Omeka\ModuleManager')
        );
    }
}
