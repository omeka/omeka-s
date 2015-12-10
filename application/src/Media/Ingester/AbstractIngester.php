<?php
namespace Omeka\Media\Ingester;

use Omeka\Api\Exception;
use Omeka\Stdlib\ErrorStore;
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
     * @param ErrorStore $errorStore
     * @return bool True on success, false on error
     */
    public function downloadFile($uri, $tempPath, ErrorStore $errorStore)
    {
        $client = $this->getServiceLocator()->get('Omeka\HttpClient');
        $client->setUri($uri)->setStream($tempPath);

        // Attempt three requests before throwing a Zend HTTP exception.
        $attempt = 0;
        while (true) {
            try {
                $response = $client->send();
                break;
            } catch (\Exception $e) {
                if (++$attempt === 3) {
                    $errorStore->addError('error', $e->getMessage());
                    return false;
                }
            }
        }

        if (!$response->isOk()) {
            $errorStore->addError('error', sprintf(
                'Error ingesting from URI: %s (%s)',
                $response->getReasonPhrase(),
                $response->getStatusCode()
            ));
            return false;
        }

        return true;
    }
}
