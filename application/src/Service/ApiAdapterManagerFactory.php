<?php
namespace Omeka\Service;

use Omeka\Api\Adapter\Manager as AdapterManager;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

/**
 * API adapter manager factory.
 */
class ApiAdapterManagerFactory implements FactoryInterface
{
    /**
     * Create the API adapter manager service.
     *
     * @return ApiManager
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $config = $serviceLocator->get('Config');
        if (!isset($config['api_adapters'])) {
            throw new Exception\ConfigException('Missing API adapter configuration');
        }
        return new AdapterManager($serviceLocator, $config['api_adapters']);
    }
}
