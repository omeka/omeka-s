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

        // Normalize short adapter aliases to class before check below.
        if (is_string($options['adapter'] ?? null)) {
            $aliases = [
                'curl' => Curl::class,
                'socket' => Socket::class,
            ];
            $options['adapter'] = $aliases[strtolower($options['adapter'])] ?? $options['adapter'];
        }

        // Autodetect the adapter only when none is configured (curl to support
        // HTTP/2 when available, socket otherwise).
        // An explicitly configured adapter is kept without silent fallback.
        if (empty($options['adapter'])) {
            $options['adapter'] = extension_loaded('curl') ? Curl::class : Socket::class;
        }
        $isCurl = is_a($options['adapter'], Curl::class, true);

        // Negotiate HTTP/2 over TLS when curl is used and libcurl supports it,
        // unless the http version is set explicitly.
        // Curl transparently falls back to HTTP/1.1 when server does not /2.
        if ($isCurl
            && defined('CURL_HTTP_VERSION_2TLS')
            && !isset($options['curloptions'][CURLOPT_HTTP_VERSION])
        ) {
            $options['curloptions'][CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_2TLS;
        }

        return new Client(null, $options);
    }
}
