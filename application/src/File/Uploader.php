<?php
namespace Omeka\File;

use Omeka\Stdlib\ErrorStore;
use Zend\Filter\File\RenameUpload;
use Zend\InputFilter\FileInput;

/**
 * File uploader service
 */
class Uploader
{
    /**
     * Upload a file.
     *
     * Pass the $errorStore object if an error should raise an API validation
     * error.
     *
     * @param array $fileData
     * @param TempFile $tempFile
     * @param null|ErrorStore $errorStore
     * @return bool True on success, false on error
     */
    public function upload(array $fileData, TempFile $tempFile, ErrorStore $errorStore = null)
    {
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
        return true;
    }
}
