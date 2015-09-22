<?php
namespace Omeka\Media\Ingester;

use Omeka\Api\Exception;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\Uri\Http as HttpUri;

abstract class AbstractIngester implements IngesterInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * Download a file.
     *
     * @param HttpUri|string $uri
     * @param string $tempPath
     */
    public function downloadFile($uri, $tempPath)
    {
        $client = $this->getServiceLocator()->get('Omeka\HttpClient');
        $client->setUri($uri)->setStream($tempPath);

        // Attempt three requests before throwing a Zend HTTP exception.
        $attempt = 0;
        while (true) {
            try {
                $response = $client->send();
                break;
            } catch (HttpExceptionInterface $e) {
                if (++$attempt == 3) throw $e;
            }
        }

        if (!$response->isOk()) {
            throw new Exception\RuntimeException(sprintf(
                "Error ingesting from URI: %s (%s)",
                $response->getReasonPhrase(),
                $response->getStatusCode()
            ));
        }
    }
}
