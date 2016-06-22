<?php
namespace Omeka\Service\Controller\Admin;

use Omeka\Controller\Admin\SystemInfoController;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class SystemInfoControllerFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $controllers)
    {
        $services = $controllers->getServiceLocator();
        return new SystemInfoController($services->get('Omeka\Connection'));
    }
}
