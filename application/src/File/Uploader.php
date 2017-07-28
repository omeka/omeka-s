<?php
namespace Omeka\File;

use Omeka\File\Manager as FileManager;
use Omeka\Stdlib\ErrorStore;
use Zend\Filter\File\RenameUpload;
use Zend\InputFilter\FileInput;

/**
 * File uploader service
 */
class Uploader
{
    /**
     * @var FileManager
     */
    protected $fileManager;

    /**
     * @param FileManager $fileManager
     */
    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    /**
     * Upload a file.
     *
     * Pass the $errorStore object if an error should raise an API validation
     * error.
     *
     * @param array $fileData
     * @param null|ErrorStore $errorStore
     * @return TempFile|false False on error
     */
    public function upload(array $fileData, ErrorStore $errorStore = null)
    {
        $tempFile = $this->fileManager->createTempFile();
        $fileInput = new FileInput('file');
        $fileInput->getFilterChain()->attach(new RenameUpload([
            'target' => $tempFile->getTempPath(),
            'overwrite' => true,
        ]));
        $fileInput->setValue($fileData);
        if (!$fileInput->isValid()) {
            if ($errorStore) {
                foreach ($fileInput->getMessages() as $message) {
                    $errorStore->addError('upload', $message);
                }
            }
            return false;
        }
        $fileInput->getValue();
        return $tempFile;
    }
}
