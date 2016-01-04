<?php
namespace Omeka\Media\Ingester;

use Omeka\Stdlib\ErrorStore;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\Uri\Http as HttpUri;

abstract class AbstractIngester implements IngesterInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * Download a file.
     *
     * Pass the $errorStore object if an error should raise an API validation
     * error. Returns true on success, false on error.
     *
     * @param HttpUri|string $uri
     * @param string $tempPath
     * @param ErrorStore|null $errorStore
     * @return bool
     */
    public function downloadFile($uri, $tempPath, ErrorStore $errorStore = null)
    {
        $client = $this->getServiceLocator()->get('Omeka\HttpClient');
        $client->setUri($uri)->setStream($tempPath);

        // Attempt three requests before handling an exception.
        $attempt = 0;
        while (true) {
            try {
                $response = $client->send();
                break;
            } catch (\Exception $e) {
                if (++$attempt === 3) {
                    if ($errorStore) {
                        $errorStore->addError('error', $e->getMessage());
                    }
                    $this->getServiceLocator()->get('Omeka\Logger')->err((string) $e);
                    return false;
                }
            }
        }

        if (!$response->isOk()) {
            $message = sprintf(
                'Error downloading "%s": %s %s',
                (string) $uri,
                $response->getStatusCode(),
                $response->getReasonPhrase()
            );
            if ($errorStore) {
                $errorStore->addError('error', $message);
            }
            $this->getServiceLocator()->get('Omeka\Logger')->err($message);
            return false;
        }

        return true;
    }
}
