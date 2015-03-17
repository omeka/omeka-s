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
        $destination = tempnam($tempDir, 'ingest');

        $fileInput = new FileInput('file');
        $fileInput->getFilterChain()->attach(new RenameUpload(array(
            'target' => $destination,
            'overwrite' => true
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
        chmod($destination, 0644);

        $originalFilename = $fileData['name'];
        $this->processFile($media, $destination, $originalFilename);

        if (!array_key_exists('o:source', $data)) {
            $media->setSource($originalFilename);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function form(PhpRenderer $view, array $options = array())
    {}
}
