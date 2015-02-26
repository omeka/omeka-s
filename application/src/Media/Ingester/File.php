<?php
namespace Omeka\Media\Ingester;

use Omeka\Api\Request;
use Omeka\Model\Entity\Media;
use Omeka\Stdlib\ErrorStore;
use Zend\Math\Rand;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\Uri\Http as HttpUri;

/**
 * Ingester for the file media type.
 */
class File implements IngesterInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function validateRequest(Request $request, ErrorStore $errorStore)
    {
        $data = $request->getContent();

        if (!isset($data['ingest_uri'])) {
            $errorStore->addError('ingest_uri', 'No ingest URI specified');
            return;
        }

        $uri = new HttpUri($data['ingest_uri']);
        if (!($uri->isValid() && $uri->isAbsolute())) {
            $errorStore->addError('ingest_uri', 'Invalid ingest URI specified');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function ingest(Media $media, Request $request, ErrorStore $errorStore)
    {
        $data = $request->getContent();
        $uri = new HttpUri($data['ingest_uri']);
        $client = $this->getServiceLocator()->get('Omeka\HttpClient');

        $extension = substr(strrchr($uri->getPath(), "."), 1);
        $filename = $this->getLocalFilename($extension);

        $client->setUri($uri);
        $client->setStream();

        $response = $client->send();

        if (!$response->isOk()) {
            $errorStore->addError('ingest_uri', sprintf(
                "Error ingesting from URI: %s (%s)",
                $response->getReasonPhrase(),
                $response->getStatusCode()
            ));
            return;
        }

        $destination = OMEKA_PATH . '/files/' . $filename;
        $status = @rename($response->getStreamName(), $destination);
        if (!$status) {
            $errorStore->addError('ingest_uri', 'Failed to move ingested file to the files directory');
            return;
        }

        chmod($destination, 0644);

        $media->setFilename($filename);

        if (!array_key_exists('o:source', $data)) {
            $media->setSource($uri);
        }
    }

    /**
     * Get a random filename for the ingested file.
     *
     * @param $extension Extension to append
     * @return string Random filename
     */
    protected function getLocalFilename($extension = null)
    {
        $filename = bin2hex(Rand::getBytes(20));

        if ($extension) {
            $filename .= ".{$extension}";
        }

        return $filename;
    }
}
