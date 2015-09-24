<?php
namespace Omeka\Media\Ingester;

use Omeka\Api\Request;
use Omeka\Entity\Media;
use Omeka\Stdlib\ErrorStore;
use Zend\Filter\File\RenameUpload;
use Zend\Form\Element\File;
use Zend\InputFilter\FileInput;
use Zend\Uri\Http as HttpUri;
use Zend\View\Renderer\PhpRenderer;

class Upload extends AbstractIngester
{
    /**
     * {@inheritDoc}
     */
    public function getLabel()
    {
        $translator = $this->getServiceLocator()->get('MvcTranslator');
        return $translator->translate('Upload');
    }

    /**
     * {@inheritDoc}
     */
    public function getRenderer()
    {
        return 'file';
    }

    /**
     * {@inheritDoc}
     */
    public function ingest(Media $media, Request $request,
        ErrorStore $errorStore
    ) {
        $data = $request->getContent();
        $fileData = $request->getFileData();
        if (!isset($fileData['file'])) {
            $errorStore->addError('error', 'No files were uploaded');
            return;
        }

        if (!isset($data['file_index'])) {
            $errorStore->addError('error', 'No file index was specified');
            return;
        }

        $index = $data['file_index'];
        if (!isset($fileData['file'][$index])) {
            $errorStore->addError('error', 'No file uploaded for the specified index');
            return;
        }

        $fileManager = $this->getServiceLocator()->get('Omeka\File\Manager');
        $file = $this->getServiceLocator()->get('Omeka\File');

        $fileInput = new FileInput('file');
        $fileInput->getFilterChain()->attach(new RenameUpload(array(
            'target' => $file->getTempPath(),
            'overwrite' => true
        )));

        $fileData = $fileData['file'][$index];
        $fileInput->setValue($fileData);
        if (!$fileInput->isValid()) {
            foreach($fileInput->getMessages() as $message) {
                $errorStore->addError('upload', $message);
            }
            return;
        }

        // Actually process and move the upload
        $fileInput->getValue();
        $file->setSourceName($fileData['name']);
        $hasThumbnails = $fileManager->storeThumbnails($file);
        $fileManager->storeOriginal($file);

        $media->setFilename($file->getStorageName());
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
    {
        $fileInput = new File('file[__index__]');
        $fileInput->setOptions(array(
            'label' => $view->translate('Upload File'),
        ));
        $fileInput->setAttributes(array(
            'id' => 'media-file-input-__index__',
        ));
        $field = $view->formField($fileInput);
        return $field . '<input type="hidden" name="o:media[__index__][file_index]" value="__index__">';
    }
}
