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
     * @var TempFileFactory
     */
    protected $tempFileFactory;

    /**
     * @param TempFileFactory $tempFileFactory
     */
    public function __construct(TempFileFactory $tempFileFactory)
    {
        $this->tempFileFactory = $tempFileFactory;
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
        $tempFile = $this->tempFileFactory->build();
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
