<?php declare(strict_types=1);

namespace Omeka\Service;

use Laminas\Http\Client;
use Laminas\Http\Client\Adapter\Curl;
use Laminas\Http\Client\Adapter\Socket;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class HttpClientFactory implements FactoryInterface
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

        // Use curl adapter to support HTTP/2, fallback to socket if unavailable.
        if (empty($options['adapter'])
            || ($options['adapter'] === Curl::class && !extension_loaded('curl'))
        ) {
            $options['adapter'] = extension_loaded('curl') ? Curl::class : Socket::class;
        }

        // Negotiate HTTP/2 over TLS when curl is used and libcurl supports it,
        // unless the http version is set explicitly.
        // Curl transparently falls back to HTTP/1.1 when server does not /2.
        if ($options['adapter'] === Curl::class
            && defined('CURL_HTTP_VERSION_2TLS')
            && !isset($options['curloptions'][CURLOPT_HTTP_VERSION])
        ) {
            $options['curloptions'][CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_2TLS;
        }

        return new Client(null, $options);
    }
}
