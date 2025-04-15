<?php

declare(strict_types=1);

namespace Laminas\Validator\File;

use Laminas\Translator\TranslatorInterface;
use Laminas\Validator\AbstractValidator;
use Laminas\Validator\Exception\InvalidArgumentException;

use function array_filter;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function explode;
use function in_array;
use function is_string;
use function trim;

/**
 * Validator for the mime type of a file
 *
 * @psalm-type OptionsArgument = array{
 *     mimeType?: string|list<string>,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
class MimeType extends AbstractValidator
{
    /** @var string */
    public const FALSE_TYPE = 'fileMimeTypeFalse';
    /** @var string */
    public const NOT_DETECTED = 'fileMimeTypeNotDetected';
    /** @var string */
    public const NOT_READABLE = 'fileMimeTypeNotReadable';

    /** @var array<string, string> */
    protected array $messageTemplates = [
        self::FALSE_TYPE   => "File has an incorrect mimetype of '%type%'",
        self::NOT_DETECTED => 'The mimetype could not be detected from the file',
        self::NOT_READABLE => 'File is not readable or does not exist',
    ];

    /** @var array<string, string|array<string, string>> */
    protected array $messageVariables = [
        'type' => 'type',
    ];

    /** Possibly the detected mime-type of the validated file */
    protected ?string $type = null;

    /**
     * A list of acceptable mime-types
     *
     * @var list<string>
     */
    protected readonly array $mimeTypes;

    /**
     * @param OptionsArgument $options
     */
    public function __construct(array $options = [])
    {
        $mimeType        = $options['mimeType'] ?? null;
        $this->mimeTypes = $this->resolveMimeType($mimeType);

        unset($options['mimeType']);

        parent::__construct($options);
    }

    /**
     * Returns true if the mimetype of the file matches the given ones. Also parts
     * of mimetypes can be checked. If you give for example "image" all image
     * mime types will be accepted like "image/gif", "image/jpeg" and so on.
     */
    public function isValid(mixed $value): bool
    {
        if (! FileInformation::isPossibleFile($value)) {
            $this->error(static::NOT_READABLE);

            return false;
        }

        $fileInfo = FileInformation::factory($value);
        $this->setValue($fileInfo->path);

        if (! $fileInfo->readable) {
            $this->error(static::NOT_READABLE);
            return false;
        }

        $this->type = $fileInfo->detectMimeType();

        if (in_array($this->type, $this->mimeTypes, true)) {
            return true;
        }

        $types = explode('/', $this->type);
        $types = array_merge($types, explode('-', $this->type));
        $types = array_merge($types, explode(';', $this->type));
        foreach ($this->mimeTypes as $mime) {
            if (in_array($mime, $types)) {
                return true;
            }
        }

        $this->error(static::FALSE_TYPE);
        return false;
    }

    /**
     * Resolve the mime-type argument from a string or an array
     *
     * @param string|list<string>|null $types
     * @return list<string>
     * @throws InvalidArgumentException When the mime-type list is empty.
     */
    protected function resolveMimeType(string|array|null $types): array
    {
        if ($types === [] || $types === '' || $types === null) {
            throw new InvalidArgumentException(
                'The `mimeType` option is required and must be a string, or a list of strings',
            );
        }

        if (is_string($types)) {
            $types = explode(',', $types);
        }

        return array_values(array_unique(array_filter(array_map(
            static fn (string $type): string => trim($type),
            $types,
        ))));
    }
}
