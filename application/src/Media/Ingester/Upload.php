<?php
namespace Omeka\Media\Ingester;

use Omeka\Api\Request;
use Omeka\Entity\Media;
use Omeka\File\Uploader;
use Omeka\File\Validator;
use Omeka\Stdlib\ErrorStore;
use Zend\Form\Element\File;
use Zend\View\Renderer\PhpRenderer;

class Upload implements IngesterInterface
{
    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var Uploader
     */
    protected $uploader;

    public function __construct(Validator $validator, Uploader $uploader)
    {
        $this->validator = $validator;
        $this->uploader = $uploader;
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel()
    {
        return 'Upload'; // @translate
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
    public function ingest(Media $media, Request $request, ErrorStore $errorStore)
    {
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

        $tempFile = $this->uploader->upload($fileData['file'][$index], $errorStore);
        if (!$tempFile) {
            return;
        }

        $tempFile->setSourceName($fileData['file'][$index]['name']);
        if (!$this->validator->validate($tempFile, $errorStore)) {
            return;
        }

        $media->setStorageId($tempFile->getStorageId());
        $media->setExtension($tempFile->getExtension());
        $media->setMediaType($tempFile->getMediaType());
        $media->setSha256($tempFile->getSha256());
        $media->setSize($tempFile->getSize());
        $hasThumbnails = $tempFile->storeThumbnails();
        $media->setHasThumbnails($hasThumbnails);
        $media->setHasOriginal(true);
        if (!array_key_exists('o:source', $data)) {
            $media->setSource($fileData['file'][$index]['name']);
        }
        $tempFile->storeOriginal();
        $tempFile->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function form(PhpRenderer $view, array $options = [])
    {
        $fileInput = new File('file[__index__]');
        $fileInput->setOptions([
            'label' => 'Upload file', // @translate
            'info' => $view->uploadLimit(),
        ]);
        $fileInput->setAttributes([
            'id' => 'media-file-input-__index__',
            'required' => true,
        ]);
        $field = $view->formRow($fileInput);
        return $field . '<input type="hidden" name="o:media[__index__][file_index]" value="__index__">';
    }
}
