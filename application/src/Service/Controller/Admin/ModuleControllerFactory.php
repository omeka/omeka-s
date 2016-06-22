<?php
namespace Omeka\Service\Controller\Admin;

use Omeka\Controller\Admin\ModuleController;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ModuleControllerFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $controllers)
    {
        $services = $controllers->getServiceLocator();
        return new ModuleController(
            $services->get('ViewRenderer'),
            $services->get('ModuleManager'),
            $services->get('Omeka\ModuleManager')
        );
    }
}
