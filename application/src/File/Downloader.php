<?php
namespace Omeka\File;

use Omeka\Stdlib\ErrorStore;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * File downloader service
 */
class Downloader
{
    /**
     * @var ServiceLocatorInterface
     */
    protected $services;

    /**
     * @param ServiceLocatorInterface $services
     */
    public function __construct(ServiceLocatorInterface $services)
    {
        $this->services = $services;
    }

    /**
     * Download a file.
     *
     * Downloads a file from a remote $uri to a local $filename. Pass the
     * $errorStore object if an error should raise an API validation error.
     *
     * @param string/\Zend\Uri\Http $uri
     * @param string $filename
     * @param null|ErrorStore $errorStore
     * @return bool True on success, false on error
     */
    public function download($uri, $filename, ErrorStore $errorStore = null)
    {
        $client = $this->services->get('Omeka\HttpClient');
        $logger = $this->services->get('Omeka\Logger');

        // Disable compressed response; it's broken alongside streaming
        $client->getRequest()->getHeaders()->addHeaderLine('Accept-Encoding', 'identity');
        $client->setUri($uri)->setStream($filename);

        // Attempt three requests before handling an exception.
        $attempt = 0;
        while (true) {
            try {
                $response = $client->send();
                break;
            } catch (\Exception $e) {
                if (++$attempt === 3) {
                    $logger->err((string) $e);
                    if ($errorStore) {
                        $message = sprintf(
                            'Error downloading %s: %s',
                            (string) $uri,
                            $e->getMessage()
                        );
                        $errorStore->addError('download', $message);
                    }
                    return false;
                }
            }
        }

        if (!$response->isOk()) {
            $message = sprintf(
                'Error downloading %s: %s %s',
                (string) $uri,
                $response->getStatusCode(),
                $response->getReasonPhrase()
            );
            if ($errorStore) {
                $errorStore->addError('download', $message);
            }
            $logger->err($message);
            return false;
        }

        return true;
    }
}
