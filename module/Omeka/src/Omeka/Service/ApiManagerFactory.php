<?php
namespace Omeka\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Omeka\Api\Manager as ApiManager;

/**
 * Factory for creating the Omeka API manager service.
 */
class ApiManagerFactory implements FactoryInterface
{
    /**
     * Create the API manager service.
     * 
     * @param ServiceLocatorInterface $serviceLocator
     * @return ApiManager
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');
        $resources = $config['api_manager']['resources'];
        
        $apiManager = new ApiManager;
        $apiManager->registerResources($resources);
        return $apiManager;
    }
}
