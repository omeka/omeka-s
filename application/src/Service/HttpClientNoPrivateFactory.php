<?php
namespace Omeka\Service;

use Omeka\Http\ClientNoPrivate;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class HttpClientNoPrivateFactory implements FactoryInterface
{
    /**
     * Create an HTTP Client instance.
     *
     * @return Client
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, ?array $options = null)
    {
        $config = $serviceLocator->get('Config');
        $options = [];
        if (isset($config['http_client']) && is_array($config['http_client'])) {
            $options = $config['http_client'];
        }
        return new ClientNoPrivate(null, $options);
    }
}
