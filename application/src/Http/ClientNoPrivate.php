<?php

namespace Omeka\Http;

use ArrayObject;
use Laminas\Http\Client;
use Laminas\Http\Client\Adapter\Curl;
use Laminas\Http\Client\Adapter\Socket;
use Laminas\Uri\Http;
use Omeka\Http\Client\Adapter\SocketNoPrivate;
use Omeka\Http\Client\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\IpUtils;

use const CURLOPT_RESOLVE;
use const DNS_AAAA;
use const FILTER_FLAG_NO_RES_RANGE;
use const FILTER_FLAG_NO_PRIV_RANGE;
use const FILTER_VALIDATE_IP;

/**
 * Http client that blocks requests to private IPs
 *
 * Useful when URLs can be provided from untrusted sources.
 */
class ClientNoPrivate extends Client
{
    protected ArrayObject $resolvedHosts;

    public function __construct($uri = null, $options = null)
    {
        $this->resolvedHosts = new ArrayObject;
        // These need to be written like this as the upstream Client normalizes
        // option keys to this single-word format
        $this->config['privatehostsallow'] = [];
        $this->config['privatehostsallowstrict'] = false;
        parent::__construct($uri, $options);
    }

    public function setAdapter($adapter)
    {
        if (is_string($adapter) && $adapter === Socket::class) {
            $adapter = new SocketNoPrivate($this->resolvedHosts);
        }
        if ($adapter instanceof Socket && !$adapter instanceof SocketNoPrivate) {
            throw new RuntimeException('Cannot set a Socket adapter on Omeka\Http\ClientNoPrivate');
        }
        return parent::setAdapter($adapter);
    }

    /**
     * Make a request, first ensuring the host is not a private one
     *
     * @throws RuntimeException
     */
    protected function doRequest(Http $uri, $method, $secure = false, $headers = [], $body = '')
    {
        if (!$this->isHostAllowed($uri)) {
            throw new RuntimeException('Cannot make request to a private host');
        }

        $host = $uri->getHost();

        // All allowed hosts are either allowlisted or were resolved.
        // If resolved, pin the curl adapter to the resolved IP
        if (isset($this->resolvedHosts[$host]) && $this->adapter instanceof Curl) {
            $port = $uri->getPort();
            $resolvedIp = $this->resolvedHosts[$host];
            $this->adapter->setCurlOption(CURLOPT_FOLLOWLOCATION, 0);
            $this->adapter->setCurlOption(CURLOPT_DNS_USE_GLOBAL_CACHE, 0);
            $this->adapter->setCurlOption(CURLOPT_RESOLVE, ["$host:$port:$resolvedIp"]);
        }

        return parent::doRequest($uri, $method, $secure, $headers, $body);
    }

    /**
     * Check if a given URL points to an allowed host
     *
     * @return bool
     */
    protected function isHostAllowed(Http $uri)
    {
        $host = $uri->getHost();
        $port = $uri->getPort();
        $scheme = $uri->getScheme();

        if (!$scheme || !$host) {
            return false;
        }

        $hostWithPort = $port ? "$host:$port" : $host;
        if (isset($this->config['privatehostsallow'][$hostWithPort])) {
            return $this->config['privatehostsallow'][$hostWithPort];
        }

        if ($this->config['privatehostsallowstrict']) {
            return false;
        }

        $ip = $this->resolve($host);
        return !$this->isIpPrivate($ip);
    }

    /**
     * Resolve a host to an IP address
     *
     * Resolved addresses are saved by the client to avoid re-resolving
     * and to pin requests to the resolved address
     *
     * @param string $host
     * @return string
     * @throws RuntimeException
     */
    protected function resolve($host)
    {
        if (($ip = filter_var(trim($host, '[]'), FILTER_VALIDATE_IP))) {
            return $ip;
        }

        if (isset($this->resolvedHosts[$host])) {
            return $this->resolvedHosts[$host];
        }

        // v4
        if (($ip = gethostbyname($host))) {
            $this->resolvedHosts[$host] = $ip;
            return $ip;
        }

        // v6
        if (($records = dns_get_record($host, DNS_AAAA))) {
            $this->resolvedHosts[$host] = $records[0]['ipv6'];
            return $ip;
        }

        throw new RuntimeException('Unable to resolve host');
    }

    /**
     * Check if an IP address is private
     *
     * @param string $ip
     * @return bool
     */
    protected function isIpPrivate($ip)
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_NO_PRIV_RANGE) === false) {
            return true;
        }

        if (IpUtils::checkIp($ip, IpUtils::PRIVATE_SUBNETS)) {
            return true;
        }

        return false;
    }
}
