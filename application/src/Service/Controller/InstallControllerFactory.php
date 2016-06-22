<?php
namespace Omeka\Service\Controller;

use Omeka\Controller\InstallController;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class InstallControllerFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $controllers)
    {
        $services = $controllers->getServiceLocator();
        return new InstallController($services->get('Omeka\Installer'));
    }
}
