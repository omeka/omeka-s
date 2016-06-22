<?php
namespace Omeka\Service\ControllerPlugin;

use Omeka\Mvc\Controller\Plugin\Api;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ApiFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $plugins)
    {
        return new Api($plugins->getServiceLocator()->get('Omeka\ApiManager'));
    }
}
