<?php
namespace Omeka\Service;

use Omeka\Api\Adapter\Manager as AdapterManager;
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
        if (!isset($config['api_adapters'])) {
            throw new Exception\ConfigException('Missing API adapter configuration');
        }
        return new AdapterManager($serviceLocator, $config['api_adapters']);
    }
}
