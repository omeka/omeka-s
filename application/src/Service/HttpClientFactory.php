<?php
namespace Omeka\Service;

use Zend\Http\Client;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class HttpClientFactory implements FactoryInterface
{
    /**
     * Create an HTTP Client instance.
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return Client
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');
        $options = array();
        if (isset($config['http_client']) && is_array($config['http_client'])) {
            $options = $config['http_client'];
        }
        return new Client(null, $options);
    }
}
