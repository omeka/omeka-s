<?php
namespace Omeka\Media\Handler;

use Omeka\Api\Request;
use Omeka\Media\Handler\AbstractFileHandler;
use Omeka\Model\Entity\Media;
use Omeka\Stdlib\ErrorStore;
use Zend\Http\Exception\ExceptionInterface as HttpExceptionInterface;
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

        $services = $this->getServiceLocator();
        $tempDir = $services->get('Config')['temp_dir'];
        $client = $services->get('Omeka\HttpClient');
        $client->setUri($uri)->setStream(tempnam($tempDir, 'ingest'));

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

        chmod($origin, 0644);
        $fileStore = $services->get('Omeka\FileStore');
        $fileStore->put($origin, $baseName);

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
