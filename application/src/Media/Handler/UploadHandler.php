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

        $file = $this->getServiceLocator()->get('Omeka\StorableFile');

        $fileInput = new FileInput('file');
        $fileInput->getFilterChain()->attach(new RenameUpload(array(
            'target' => $file->getTempPath(),
            'overwrite' => true
        )));

        $fileData = $request->getFileData()['file'];
        $fileInput->setValue($fileData);
        if (!$fileInput->isValid()) {
            foreach($fileInput->getMessages() as $message) {
                $errorStore->addError('upload', $message);
            }
            return;
        }

        // Actually process and move the upload
        $fileInput->getValue();
        chmod($file->getTempPath(), 0644);

        $hasThumbnails = $file->storeThumbnails($fileData['name']);
        $file->store($fileData['name']);

        $media->setFilename($file->getStoredName());
        $media->setMediaType($file->getMediaType());
        $media->setHasThumbnails($hasThumbnails);
        $media->setHasOriginal(true);

        if (!array_key_exists('o:source', $data)) {
            $media->setSource($fileData['name']);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function form(PhpRenderer $view, array $options = array())
    {}
}
