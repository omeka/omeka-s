<?php
namespace Omeka\Service\Controller;

use Omeka\Controller\ApiController;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ApiControllerFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $controllers)
    {
        $services = $controllers->getServiceLocator();
        return new ApiController(
            $services->get('Omeka\Paginator'),
            $services->get('Omeka\Logger')
        );
    }
}
