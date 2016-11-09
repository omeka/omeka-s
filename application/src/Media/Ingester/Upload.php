<?php
namespace Omeka\Media\Ingester;

use Omeka\Api\Request;
use Omeka\Entity\Media;
use Omeka\File\Manager as FileManager;
use Omeka\Stdlib\ErrorStore;
use Zend\Filter\File\RenameUpload;
use Zend\Form\Element\File;
use Zend\InputFilter\FileInput;
use Zend\View\Renderer\PhpRenderer;

class Upload implements IngesterInterface
{
    /**
     * @var FileManager
     */
    protected $fileManager;

    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
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

        $fileManager = $this->fileManager;
        $file = $fileManager->getTempFile();

        $fileInput = new FileInput('file');
        $fileInput->getFilterChain()->attach(new RenameUpload([
            'target' => $file->getTempPath(),
            'overwrite' => true
        ]));

        $fileData = $fileData['file'][$index];
        $fileInput->setValue($fileData);
        if (!$fileInput->isValid()) {
            foreach($fileInput->getMessages() as $message) {
                $errorStore->addError('upload', $message);
            }
            return;
        }
        $fileInput->getValue();
        $file->setSourceName($fileData['name']);
        if (!$fileManager->validateFile($file, $errorStore)) {
            return;
        }

        $media->setStorageId($file->getStorageId());
        $media->setExtension($file->getExtension($fileManager));
        $media->setMediaType($file->getMediaType());
        $media->setSha256($file->getSha256());
        $media->setHasThumbnails($fileManager->storeThumbnails($file));
        $media->setHasOriginal(true);
        if (!array_key_exists('o:source', $data)) {
            $media->setSource($fileData['name']);
        }
        $fileManager->storeOriginal($file);
        $file->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function form(PhpRenderer $view, array $options = [])
    {
        $fileInput = new File('file[__index__]');
        $fileInput->setOptions([
            'label' => 'Upload File', // @translate
            'info' => $view->uploadLimit(),
        ]);
        $fileInput->setAttributes([
            'id' => 'media-file-input-__index__',
        ]);
        $field = $view->formRow($fileInput);
        return $field . '<input type="hidden" name="o:media[__index__][file_index]" value="__index__">';
    }
}
