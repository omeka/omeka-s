<?php
namespace Omeka\Service\Controller\Admin;

use Omeka\Controller\Admin\UserController;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class UserControllerFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $controllers)
    {
        $services = $controllers->getServiceLocator();
        return new UserController($services->get('Omeka\EntityManager'));
    }
}
