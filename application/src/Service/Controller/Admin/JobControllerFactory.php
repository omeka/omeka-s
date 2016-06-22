<?php
namespace Omeka\Service\Controller\Admin;

use Omeka\Controller\Admin\JobController;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class JobControllerFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $controllers)
    {
        $services = $controllers->getServiceLocator();
        return new JobController($services->get('Omeka\JobDispatcher'));
    }
}
