<?php
namespace Omeka\Media\Ingester;

use Omeka\Api\Request;
use Omeka\Model\Entity\Media;
use Omeka\Stdlib\ErrorStore;
use Zend\Filter\File\RenameUpload;
use Zend\InputFilter\FileInput;
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
    {}

    /**
     * {@inheritDoc}
     */
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

    /**
     * Ingest from the passed ingest_uri
     */
    public function ingestFromUri(Media $media, Request $request, ErrorStore $errorStore)
    {
        $data = $request->getContent();
        $uri = new HttpUri($data['ingest_uri']);
        if (!($uri->isValid() && $uri->isAbsolute())) {
            $errorStore->addError('ingest_uri', 'Invalid ingest URI specified');
            return;
        }

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
     * Ingest from the uploaded file 'file'
     */
    protected function ingestFromUpload(Media $media, Request $request, ErrorStore $errorStore)
    {
        $fileData = $request->getFileData()['file'];
        $originalFilename = $fileData['name'];
        $extension = substr(strrchr($originalFilename, '.'), 1);
        $filename = $this->getLocalFilename($extension);
        $destination = OMEKA_PATH . '/files/' . $filename;

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

        $media->setFilename($filename);
        if (!array_key_exists('o:source', $request->getContent())) {
            $media->setSource($originalFilename);
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
