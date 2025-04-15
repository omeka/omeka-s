<?php

declare(strict_types=1);

namespace Laminas\Validator\File;

use Laminas\Translator\TranslatorInterface;
use Laminas\Validator\AbstractValidator;

use function assert;
use function implode;
use function is_string;

/**
 * Validator for the excluding file extensions
 *
 * @psalm-type OptionsArgument = array{
 *     case?: bool,
 *     extension: non-empty-string|list<non-empty-string>,
 *     allowNonExistentFile?: bool,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
final class ExcludeExtension extends AbstractValidator
{
    public const FALSE_EXTENSION    = 'fileExcludeExtensionFalse';
    public const NOT_FOUND          = 'fileExcludeExtensionNotFound';
    public const ERROR_INVALID_TYPE = 'fileExcludeExtensionInvalidType';

    /** @var array<string, string> */
    protected array $messageTemplates = [
        self::FALSE_EXTENSION    => 'File has an incorrect extension',
        self::NOT_FOUND          => 'File is not readable or does not exist',
        self::ERROR_INVALID_TYPE => 'The value is neither a file, nor a string',
    ];

    /** @var array<string, string|array<string, string>> */
    protected array $messageVariables = [
        'extension' => 'extensionList',
    ];

    private readonly bool $caseSensitive;
    private readonly bool $allowNonExistentFile;
    /** @var non-empty-list<non-empty-string> */
    private readonly array $extensions;
    /**
     * A CSV list of allowed extensions for error messages
     *
     * @var non-empty-string
     */
    protected readonly string $extensionList;

    /**
     * Sets validator options
     *
     * @param OptionsArgument $options
     */
    public function __construct(array $options)
    {
        $this->caseSensitive        = $options['case'] ?? false;
        $this->allowNonExistentFile = $options['allowNonExistentFile'] ?? false;
        $this->extensions           = Extension::resolveExtensionList($options['extension'] ?? []);
        $this->extensionList        = implode(', ', $this->extensions);

        unset($options['case'], $options['allowNonExistentFile'], $options['extension']);

        parent::__construct($options);
    }

    /**
     * Returns true if and only if the file extension of $value is not included in the
     * set extension list
     */
    public function isValid(mixed $value): bool
    {
        $this->setValue($value);
        $isFile = FileInformation::isPossibleFile($value);

        if (! $isFile && ! $this->allowNonExistentFile) {
            $this->error(self::NOT_FOUND);

            return false;
        }

        if (! $isFile && ! is_string($value) || $value === '') {
            $this->error(self::ERROR_INVALID_TYPE);

            return false;
        }

        if ($isFile) {
            $file     = FileInformation::factory($value);
            $fileName = $file->clientFileName ?? $file->baseName;
        } else {
            $fileName = $value;
        }

        assert($fileName !== '');

        $this->value = $fileName;

        $extensions = Extension::listPossibleFileNameExtensions($fileName);

        if (Extension::extensionFoundInList($this->extensions, $extensions, $this->caseSensitive)) {
            $this->error(self::FALSE_EXTENSION);

            return false;
        }

        return true;
    }
}
