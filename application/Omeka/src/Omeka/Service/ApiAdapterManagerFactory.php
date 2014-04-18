<?php
namespace Omeka\Service;

use Omeka\Api\Exception;
use Omeka\Api\Adapter\Manager as AdapterManager;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * API adapter manager factory.
 */
class ApiAdapterManagerFactory implements FactoryInterface
{
    /**
     * Create the API adapter manager service.
     * 
     * @param ServiceLocatorInterface $serviceLocator
     * @return ApiManager
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');
        return new AdapterManager(new Config($config['api_adapters']));
    }
}
