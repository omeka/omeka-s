<?php
namespace Omeka\Media\Handler;

use Omeka\Api\Request;
use Omeka\Media\Handler\AbstractFileHandler;
use Omeka\Model\Entity\Media;
use Omeka\Stdlib\ErrorStore;
use Zend\Filter\File\RenameUpload;
use Zend\InputFilter\FileInput;
use Zend\Uri\Http as HttpUri;
use Zend\View\Renderer\PhpRenderer;

class UploadHandler extends AbstractFileHandler
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
        $fileData = $request->getFileData();
        if (!isset($fileData['file'])) {
            $errorStore->addError('error', 'No URL ingest data specified');
            return;
        }

        $services = $this->getServiceLocator();
        $tempDir = $services->get('Config')['temp_dir'];

        $fileData = $request->getFileData()['file'];
        $originalFilename = $fileData['name'];
        $mediaType = $this->getMediaType($fileData['tmp_name']);
        $extension = $this->getExtension($originalFilename, $mediaType);
        $baseName = $this->getLocalBaseName($extension);
        $destination = $tempDir . DIRECTORY_SEPARATOR . $baseName;

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

        $fileStore = $services->get('Omeka\FileStore');
        $fileStore->put($destination, $baseName);

        $media->setFilename($baseName);
        $media->setMediaType($mediaType);
        if (!array_key_exists('o:source', $request->getContent())) {
            $media->setSource($originalFilename);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function form(PhpRenderer $view, array $options = array())
    {}
}
