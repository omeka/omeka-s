<?php

declare(strict_types=1);

namespace Laminas\Validator\File;

use function array_merge;
use function explode;
use function in_array;

/**
 * Validator for the mime type of a file
 */
final class ExcludeMimeType extends MimeType
{
    public const FALSE_TYPE   = 'fileExcludeMimeTypeFalse';
    public const NOT_DETECTED = 'fileExcludeMimeTypeNotDetected';
    public const NOT_READABLE = 'fileExcludeMimeTypeNotReadable';

    /** @var array<string, string> */
    protected array $messageTemplates = [
        self::FALSE_TYPE   => "File has an incorrect mimetype of '%type%'",
        self::NOT_DETECTED => 'The mimetype could not be detected from the file',
        self::NOT_READABLE => 'File is not readable or does not exist',
    ];

    /**
     * Returns true if the mimetype of the file does not match the given ones. Also parts
     * of mimetypes can be checked. If you give for example "image" all image
     * mime types will not be accepted like "image/gif", "image/jpeg" and so on.
     */
    public function isValid(mixed $value): bool
    {
        if (! FileInformation::isPossibleFile($value)) {
            $this->error(self::NOT_READABLE);

            return false;
        }

        $fileInfo = FileInformation::factory($value);
        $this->setValue($fileInfo->path);

        if (! $fileInfo->readable) {
            $this->error(self::NOT_READABLE);
            return false;
        }

        $this->type = $fileInfo->detectMimeType();

        if (in_array($this->type, $this->mimeTypes, true)) {
            $this->error(self::FALSE_TYPE);
            return false;
        }

        $types = explode('/', $this->type);
        $types = array_merge($types, explode('-', $this->type));
        $types = array_merge($types, explode(';', $this->type));
        foreach ($this->mimeTypes as $mime) {
            if (in_array($mime, $types)) {
                $this->error(self::FALSE_TYPE);
                return false;
            }
        }

        return true;
    }
}
