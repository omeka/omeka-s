<?php
namespace Omeka\Service;

use Omeka\Api\Exception;
use Omeka\Api\Manager as ApiManager;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * API manager factory.
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
        if (!isset($config['api_manager']['resources'])) {
            throw new Exception\ConfigException('The configuration has no registered API resources.');
        }
        $resources = $config['api_manager']['resources'];
        $apiManager = new ApiManager;
        $apiManager->registerResources($resources);
        return $apiManager;
    }
}
