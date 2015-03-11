<?php
namespace Omeka\Media\Handler;

use Omeka\Api\Request;
use Omeka\Media\Handler\AbstractFileHandler;
use Omeka\Model\Entity\Media;
use Omeka\Stdlib\ErrorStore;
use Zend\Uri\Http as HttpUri;
use Zend\View\Renderer\PhpRenderer;

class UrlHandler extends AbstractFileHandler
{
    /**
     * {@inheritDoc}
     */
    public function validateRequest(Request $request, ErrorStore $errorStore)
    {}

    /**
     * {@inheritDoc}
     */
    public function ingest(Media $media, Request $request, ErrorStore $errorStore)
    {
        $data = $request->getContent();
        if (!isset($data['ingest_uri'])) {
            $errorStore->addError('error', 'No URL ingest data specified');
            return;
        }

        $uri = new HttpUri($data['ingest_uri']);
        if (!($uri->isValid() && $uri->isAbsolute())) {
            $errorStore->addError('ingest_uri', 'Invalid ingest URI specified');
            return;
        }

        $client = $this->getServiceLocator()->get('Omeka\HttpClient');
        $client->setUri($uri)->setStream();
        $response = $client->send();

        if (!$response->isOk()) {
            $errorStore->addError('ingest_uri', sprintf(
                "Error ingesting from URI: %s (%s)",
                $response->getReasonPhrase(),
                $response->getStatusCode()
            ));
            return;
        }

        $origin = $response->getStreamName();
        $mediaType = $this->getMediaType($origin);
        $extension = $this->getExtension($uri->getPath(), $mediaType);
        $baseName = $this->getLocalBaseName($extension);
        $destination = sprintf('%s/files/%s', OMEKA_PATH, $baseName);
        $status = @rename($origin, $destination);

        if (!$status) {
            $errorStore->addError('ingest_uri', 'Failed to move ingested file to the files directory');
            return;
        }

        chmod($destination, 0644);

        $media->setFilename($baseName);
        $media->setMediaType($mediaType);
        if (!array_key_exists('o:source', $data)) {
            $media->setSource($uri);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function form(PhpRenderer $view, array $options = array())
    {}
}
