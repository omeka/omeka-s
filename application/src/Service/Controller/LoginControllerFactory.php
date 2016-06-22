<?php
namespace Omeka\Service\Controller;

use Omeka\Controller\LoginController;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class LoginControllerFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $controllers)
    {
        $services = $controllers->getServiceLocator();
        return new LoginController(
            $services->get('Omeka\EntityManager'),
            $services->get('Omeka\AuthenticationService')
        );
    }
}
