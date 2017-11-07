<?php
namespace Omeka\File;

use Omeka\Stdlib\ErrorStore;
use Omeka\Stdlib\Message;

/**
 * File validator service
 */
class Validator
{
    /**
     * @var array
     */
    protected $mediaTypes;

    /**
     * @var array
     */
    protected $extensions;

    /**
     * @var bool
     */
    protected $disable = false;

    /**
     * @param null|array $mediaTypes Media type whitelist
     * @param null|array $extensions Extension whitelist
     * @param bool $disable Whether to disable validation
     */
    public function __construct(array $mediaTypes = null, array $extensions = null, $disable = false)
    {
        $this->mediaTypes = $mediaTypes;
        $this->extensions = $extensions;
        $this->disable = $disable;
    }

    /**
     * Validate a file.
     *
     * Validates a file against the media type and extension whitelists. Prior
     * to calling this method the file must be saved to `TempFile::$tempPath`
     * and the file's original filename must be saved to `TempFile::$sourceName`.
     *
     * Pass the $errorStore object if an error should raise an API validation
     * error.
     *
     * @param TempFile $tempFile
     * @param null|ErrorStore $errorStore
     * @return bool
     */
    public function validate(TempFile $tempFile, ErrorStore $errorStore = null)
    {
        $isValid = true;
        if ($this->disable) {
            return $isValid;
        }
        if (null !== $this->mediaTypes) {
            $mediaType = $tempFile->getMediaType();
            if (!in_array($mediaType, $this->mediaTypes)) {
                $isValid = false;
                if ($errorStore) {
                    $message = new Message(
                        'Error validating "%s". Cannot store files with the media type "%s".', // @translate
                        $tempFile->getSourceName(), $mediaType
                        );
                    $errorStore->addError('file', $message);
                }
            }
        }
        if (null !== $this->extensions) {
            $extension = $tempFile->getExtension();
            if (!in_array($extension, $this->extensions)) {
                $isValid = false;
                if ($errorStore) {
                    $message = new Message(
                        'Error validating "%s". Cannot store files with the resolved extension "%s".', // @translate
                        $tempFile->getSourceName(), $extension
                        );
                    $errorStore->addError('file', $message);
                }
            }
        }
        return $isValid;
    }
}
