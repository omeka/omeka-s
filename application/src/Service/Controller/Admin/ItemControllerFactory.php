<?php
namespace Omeka\Service\Controller\Admin;

use Omeka\Controller\Admin\ItemController;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ItemControllerFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $controllers)
    {
        $services = $controllers->getServiceLocator();
        return new ItemController($services->get('Omeka\MediaIngesterManager'));
    }
}
