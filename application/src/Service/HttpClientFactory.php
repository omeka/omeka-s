<?php
namespace Omeka\Service;

use Zend\Http\Client;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class HttpClientFactory implements FactoryInterface
{
    /**
     * Create an HTTP Client instance.
     *
     * @return Client
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $config = $serviceLocator->get('Config');
        $options = [];
        if (isset($config['http_client']) && is_array($config['http_client'])) {
            $options = $config['http_client'];
        }
        return new Client(null, $options);
    }
}
