<?php

namespace Omeka\Http\Client\Adapter;

use ArrayObject;
use Laminas\Http\Client\Adapter\Exception as AdapterException;
use Laminas\Http\Client\Adapter\Socket;
use Laminas\Stdlib\ErrorHandler;

use function gettype;
use function is_numeric;
use function is_resource;
use function sprintf;
use function stream_context_set_option;
use function stream_set_timeout;
use function stream_socket_client;
use function strlen;
use function strpos;
use function substr;

use const STREAM_CLIENT_CONNECT;
use const STREAM_CLIENT_PERSISTENT;

/**
 * Socket adapter with pinning support for NoPrivate HTTP client
 */
class SocketNoPrivate extends Socket
{
    protected ArrayObject $resolvedHosts;

    public function __construct(ArrayObject $resolvedHosts)
    {
        $this->resolvedHosts = $resolvedHosts;
    }

    /**
     * Connect to the remote server, pinning to any IPs resolved by the client
     *
     * @param string $host
     * @param int $port
     * @param bool $secure
     * @throws AdapterException\RuntimeException
     */
    public function connect($host, $port = 80, $secure = false)
    {
        // SocketNoPrivate: check if we have resolved this host in the client
        $hasResolvedHost = isset($this->resolvedHosts[$host]);

        // If we are connected to the wrong host, disconnect first
        $connectedTo = $this->connectedTo[0] ?? '';
        $connectedHost = strpos($connectedTo, '://')
            ? substr($connectedTo, strpos($connectedTo, '://') + 3, strlen($connectedTo))
            : $connectedTo;

        if ($connectedHost !== $host || $this->connectedTo[1] !== $port) {
            if (is_resource($this->socket)) {
                $this->close();
            }
        }

        // Now, if we are not connected, connect
        if (!is_resource($this->socket) || !$this->config['keepalive']) {
            $context = $this->getStreamContext();

            if ($secure || $this->config['sslusecontext']) {
                if ($this->config['sslverifypeer'] !== null) {
                    if (!stream_context_set_option($context, 'ssl', 'verify_peer', $this->config['sslverifypeer'])) {
                        throw new AdapterException\RuntimeException('Unable to set sslverifypeer option');
                    }
                }

                if ($this->config['sslcafile']) {
                    if (!stream_context_set_option($context, 'ssl', 'cafile', $this->config['sslcafile'])) {
                        throw new AdapterException\RuntimeException('Unable to set sslcafile option');
                    }
                }

                if ($this->config['sslcapath']) {
                    if (!stream_context_set_option($context, 'ssl', 'capath', $this->config['sslcapath'])) {
                        throw new AdapterException\RuntimeException('Unable to set sslcapath option');
                    }
                }

                if ($this->config['sslallowselfsigned'] !== null) {
                    if (
                        !stream_context_set_option(
                            $context,
                            'ssl',
                            'allow_self_signed',
                            $this->config['sslallowselfsigned']
                        )
                    ) {
                        throw new AdapterException\RuntimeException('Unable to set sslallowselfsigned option');
                    }
                }

                if ($this->config['sslcert'] !== null) {
                    if (!stream_context_set_option($context, 'ssl', 'local_cert', $this->config['sslcert'])) {
                        throw new AdapterException\RuntimeException('Unable to set sslcert option');
                    }
                }

                if ($this->config['sslpassphrase'] !== null) {
                    if (!stream_context_set_option($context, 'ssl', 'passphrase', $this->config['sslpassphrase'])) {
                        throw new AdapterException\RuntimeException('Unable to set sslpassphrase option');
                    }
                }

                if ($this->config['sslverifypeername'] !== null) {
                    if (
                        !stream_context_set_option(
                            $context,
                            'ssl',
                            'verify_peer_name',
                            $this->config['sslverifypeername']
                        )
                    ) {
                        throw new AdapterException\RuntimeException('Unable to set sslverifypeername option');
                    }
                }

                // SocketNoPrivate: if pinning, manually set peer name for TLS
                if ($hasResolvedHost) {
                    if (!stream_context_set_option($context, 'ssl', 'peer_name', $host)) {
                        throw new AdapterException\RuntimeException('Unable to set peer_name');
                    }
                }
            }

            $flags = STREAM_CLIENT_CONNECT;
            if ($this->config['persistent']) {
                $flags |= STREAM_CLIENT_PERSISTENT;
            }

            if (isset($this->config['connecttimeout'])) {
                $connectTimeout = $this->config['connecttimeout'];
            } else {
                $connectTimeout = $this->config['timeout'];
            }

            if ($connectTimeout !== null && !is_numeric($connectTimeout)) {
                throw new AdapterException\InvalidArgumentException(sprintf(
                    'integer or numeric string expected, got %s',
                    gettype($connectTimeout)
                ));
            }

            ErrorHandler::start();

            // SocketNoPrivate: pin to the resolved IP if one exists for this host
            if ($hasResolvedHost) {
                $maybePinnedHost = $this->resolvedHosts[$host];
                if (strpos($maybePinnedHost, ':') !== false) {
                    $maybePinnedHost = "[$maybePinnedHost]";
                }
            } else {
                $maybePinnedHost = $host;
            }
            $this->socket = stream_socket_client(
                $maybePinnedHost . ':' . $port,
                $errno,
                $errstr,
                (int) $connectTimeout,
                $flags,
                $context
            );
            $error = ErrorHandler::stop();

            if (!$this->socket) {
                $this->close();
                throw new AdapterException\RuntimeException(
                    sprintf(
                        'Unable to connect to %s:%d%s',
                        $host,
                        $port,
                        $error ? ' . Error #' . $error->getCode() . ': ' . $error->getMessage() : ''
                    ),
                    0,
                    $error
                );
            }

            // Set the stream timeout
            if (!stream_set_timeout($this->socket, (int) $this->config['timeout'])) {
                throw new AdapterException\RuntimeException('Unable to set the connection timeout');
            }

            if ($secure || $this->config['sslusecontext']) {
                if ($this->setSslCryptoMethod) {
                    try {
                        $this->enableCryptoTransport($this->config['ssltransport'], $this->socket, $host);
                    } catch (AdapterException\RuntimeException $e) {
                        $this->close();
                        throw $e;
                    }
                }

                $host = $this->config['ssltransport'] . '://' . $host;
            } else {
                $host = 'tcp://' . $host;
            }

            // Update connectedTo
            $this->connectedTo = [$host, $port];
        }
    }
}
