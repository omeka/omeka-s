<?php
namespace Omeka\Media\Handler;

use finfo;
use Omeka\Api\Representation\Entity\MediaRepresentation;
use Omeka\Api\Request;
use Omeka\Media\Handler\HandlerInterface;
use Omeka\Model\Entity\Media;
use Omeka\Stdlib\ErrorStore;
use Zend\Filter\File\RenameUpload;
use Zend\InputFilter\FileInput;
use Zend\Math\Rand;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
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

    public function render(PhpRenderer $view, MediaRepresentation $media,
        array $options = array()
    ) {
        try {
            $renderer = $this->getServiceLocator()
                ->get('Omeka\FileRendererManager')
                ->get($media->mediaType());
            return $renderer->render($view, $media, $options);
        } catch (ServiceNotFoundException $e) {
            $fileStore = $this->getServiceLocator()->get('Omeka\FileStore');
            $url = $fileStore->getUri($media->filename());
            return $view->hyperlink($media->filename(), $url);
        }
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

        chmod($origin, 0644);
        $fileStore = $this->getServiceLocator()->get('Omeka\FileStore');
        $fileStore->put($origin, $baseName);

        $media->setFilename($baseName);
        $media->setMediaType($mediaType);
        if (!array_key_exists('o:source', $data)) {
            $media->setSource($uri);
        }
    }

    /**
     * Ingest from the uploaded file 'file'
     */
    protected function ingestFromUpload(Media $media, Request $request, ErrorStore $errorStore)
    {
        $fileData = $request->getFileData()['file'];
        $originalFilename = $fileData['name'];
        $mediaType = $this->getMediaType($fileData['tmp_name']);
        $extension = $this->getExtension($originalFilename, $mediaType);
        $baseName = $this->getLocalBaseName($extension);
        $destination = OMEKA_PATH . '/files/' . $baseName;

        $fileInput = new FileInput('file');
        $fileInput->getFilterChain()->attach(new RenameUpload(array(
            'target' => $destination
        )));

        $fileInput->setValue($fileData);
        if (!$fileInput->isValid()) {
            foreach($fileInput->getMessages() as $message) {
                $errorStore->addError('upload', $message);
            }
            return;
        }

        // Actually process and move the upload
        $fileInput->getValue();

        $media->setFilename($baseName);
        $media->setMediaType($mediaType);
        if (!array_key_exists('o:source', $request->getContent())) {
            $media->setSource($originalFilename);
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
