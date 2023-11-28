<?php
namespace Omeka\Media\Ingester;

use Omeka\Api\Request;
use Omeka\Entity\Media;
use Omeka\File\Uploader;
use Omeka\Stdlib\ErrorStore;
use Laminas\Form\Element\File;
use Laminas\View\Renderer\PhpRenderer;

class Upload implements IngesterInterface
{
    /**
     * @var Uploader
     */
    protected $uploader;

    public function __construct(Uploader $uploader)
    {
        $this->uploader = $uploader;
    }

    public function getLabel()
    {
        return 'Upload'; // @translate
    }

    public function getRenderer()
    {
        return 'file';
    }

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
        if (!isset($fileData['file'][$index][0])) {
            $errorStore->addError('error', 'No file uploaded for the specified index');
            return;
        }

        $tempFile = $this->uploader->upload($fileData['file'][$index][0], $errorStore);
        if (!$tempFile) {
            return;
        }

        $tempFile->setSourceName($fileData['file'][$index][0]['name']);
        if (!array_key_exists('o:source', $data)) {
            $media->setSource($fileData['file'][$index]['name'][0]);
        }
        $tempFile->mediaIngestFile($media, $request, $errorStore);
    }

    public function form(PhpRenderer $view, array $options = [])
    {
        $fileInput = new File('file[__index__]');
        $fileInput->setOptions([
            'label' => 'Upload file', // @translate
            'info' => $view->uploadLimit(),
        ]);
        $fileInput->setAttributes([
            'id' => 'media-file-input-__index__',
            'class' => 'media-file-input',
            'required' => true,
            'multiple' => true,
        ]);
        $field = $view->formRow($fileInput);
        return $field . '<input type="hidden" name="o:media[__index__][file_index]" value="__index__">';
    }
}
