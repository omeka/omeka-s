<?php
namespace Omeka\File;

use Omeka\Stdlib\ErrorStore;
use Omeka\Settings\Settings;

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
     * @param array $mediaTypes Media type whitelist
     * @param array $extensions Extension whitelist
     * @param bool $disable Whether to disable validation
     */
    public function __construct(array $mediaTypes, array $extensions, $disable = false)
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
     * @param File $file
     * @param ErrorStore $errorStore
     * @return bool
     */
    public function validate(TempFile $tempFile, ErrorStore $errorStore)
    {
        if ($this->disable) {
            return true;
        }

        $mediaType = $tempFile->getMediaType();
        $extension = $tempFile->getExtension();
        $mediaTypeIsValid = in_array($mediaType, $this->mediaTypes);
        $extensionIsValid = in_array($extension, $this->extensions);

        if (!$mediaTypeIsValid) {
            $errorStore->addError('file', new Message(
                'Error ingesting "%s". Cannot store files with the media type "%s".', // @translate
                $file->getSourceName(), $mediaType
            ));
        }
        if (!$extensionIsValid) {
            $errorStore->addError('file', new Message(
                'Error ingesting "%s". Cannot store files with the resolved extension "%s".', // @translate
                $file->getSourceName(), $extension
            ));
        }
        return $mediaTypeIsValid && $extensionIsValid;
    }
}
