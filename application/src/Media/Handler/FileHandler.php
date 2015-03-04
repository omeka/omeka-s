<?php
namespace Omeka\Media\Handler;

use finfo;
use Omeka\Api\Representation\Entity\MediaRepresentation;
use Omeka\Api\Request;
use Omeka\Media\Handler\HandlerInterface;
use Omeka\Model\Entity\Media;
use Omeka\Stdlib\ErrorStore;
use Zend\Math\Rand;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\Uri\Http as HttpUri;
use Zend\View\Renderer\PhpRenderer;

class FileHandler implements HandlerInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * @var array Map between Internet media types and file extensions.
     */
    protected $mediaTypeMap;

    public function validateRequest(Request $request, ErrorStore $errorStore)
    {}

    public function ingest(Media $media, Request $request, ErrorStore $errorStore)
    {
        $data = $request->getContent();
        $fileData = $request->getFileData();

        if (isset($fileData['file'])) {
            $this->ingestFromUpload($media, $request, $errorStore);
        } else if (isset($data['ingest_uri'])) {
            $this->ingestFromUri($media, $request, $errorStore);
        } else {
            $errorStore->addError('error', 'No file ingest data specified');
        }
    }

    public function form(PhpRenderer $view, array $options = array())
    {}

    public function render(PhpRenderer $view, MediaRepresentation $media, array $options = array())
    {
        $filename = $media->filename();
        $url = $view->basePath('files/' . $filename);
        return $view->hyperlink($filename, $url);
    }

    public function setMediaTypeMap($mediaTypeMap)
    {
        $this->mediaTypeMap = $mediaTypeMap;
    }

    /**
     * Ingest from the passed ingest_uri
     */
    protected function ingestFromUri(Media $media, Request $request, ErrorStore $errorStore)
    {
        $data = $request->getContent();

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
     * Get a random base name for the ingested file.
     *
     * @param string $extension The filename extension to append
     * @return string
     */
    protected function getLocalBaseName($extension = null)
    {
        $baseName = bin2hex(Rand::getBytes(20));
        if ($extension) {
            $baseName .= '.' . $extension;
        }
        return $baseName;
    }

    /**
     * Detect and get an Internet media type.
     *
     * @uses finfo
     * @param string $filename The path to a file
     * @return string
     */
    protected function getMediaType($filename)
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        return $finfo->file($filename);
    }

    /**
     * Get a filename extension.
     *
     * Returns the original extension if the file already has one. Otherwise it
     * returns the first extension found from a map between Internet media types
     * and extensions.
     *
     * @param string $originalFile The original file name
     * @param string $mediaType The file's Internet media type
     * @return string
     */
    protected function getExtension($originalFile, $mediaType)
    {
        $extension = substr(strrchr($originalFile, '.'), 1);
        if (!$extension && isset($this->mediaTypeMap[$mediaType][0])) {
            $extension = $this->mediaTypeMap[$mediaType][0];
        }
        return $extension;
    }
}
