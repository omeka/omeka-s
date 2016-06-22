<?php
namespace Omeka\Service\Controller;

use Omeka\Controller\MigrateController;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class MigrateControllerFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $controllers)
    {
        $services = $controllers->getServiceLocator();
        return new MigrateController($services->get('Omeka\MigrationManager'));
    }
}
