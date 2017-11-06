<?php
namespace Omeka\File;

use Omeka\Stdlib\ErrorStore;
use Zend\Log\Logger;
use Zend\ServiceManager\ServiceLocatorInterface;
use Omeka\Stdlib\Message;

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
     * @var TempFileFactory
     */
    protected $tempFileFactory;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param ServiceLocatorInterface $services
     * @param TempFileFactory $tempFileFactory
     */
    public function __construct(ServiceLocatorInterface $services,
        TempFileFactory $tempFileFactory, Logger $logger
    ) {
        $this->services = $services;
        $this->tempFileFactory = $tempFileFactory;
        $this->logger = $logger;
    }

    /**
     * Download a file from a remote URI.
     *
     * Pass the $errorStore object if an error should raise an API validation
     * error.
     *
     * @param string|\Zend\Uri\Http $uri
     * @param null|ErrorStore $errorStore
     * @return TempFile|false False on error
     */
    public function download($uri, ErrorStore $errorStore = null)
    {
        $client = $this->services->get('Omeka\HttpClient'); // non-shared service
        $tempFile = $this->tempFileFactory->build();

        // Disable compressed response; it's broken alongside streaming
        $client->getRequest()->getHeaders()->addHeaderLine('Accept-Encoding', 'identity');
        $client->setUri($uri)->setStream($tempFile->getTempPath());

        // Attempt three requests before handling an exception.
        $attempt = 0;
        while (true) {
            try {
                $response = $client->send();
                break;
            } catch (\Exception $e) {
                if (++$attempt === 3) {
                    $this->logger->err((string) $e);
                    if ($errorStore) {
                        $message = new Message(
                            'Error downloading %s: %s', // @translate
                            (string) $uri, $e->getMessage()
                            );
                        $errorStore->addError('download', $message);
                    }
                    return false;
                }
            }
        }

        if (!$response->isOk()) {
            $message = sprintf(
                'Error downloading %s: %s %s', // @translate
                (string) $uri, $response->getStatusCode(), $response->getReasonPhrase()
                );
            if ($errorStore) {
                $errorStore->addError('download', $message);
            }
            $this->logger->err($message);
            return false;
        }

        return $tempFile;
    }
}
