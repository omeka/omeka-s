<?php

declare(strict_types=1);

namespace Laminas\Validator\File;

use Laminas\Translator\TranslatorInterface;
use Laminas\Validator\AbstractValidator;
use Laminas\Validator\Exception\InvalidArgumentException;

use function array_reverse;
use function array_shift;
use function assert;
use function end;
use function explode;
use function implode;
use function is_string;
use function sprintf;
use function strtolower;
use function trim;

/**
 * Validator for the file extension of a file
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
final class Extension extends AbstractValidator
{
    public const FALSE_EXTENSION    = 'fileExtensionFalse';
    public const NOT_FOUND          = 'fileExtensionNotFound';
    public const ERROR_INVALID_TYPE = 'fileExtensionInvalidType';

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
        $this->extensions           = self::resolveExtensionList($options['extension'] ?? []);
        $this->extensionList        = implode(', ', $this->extensions);

        unset($options['case'], $options['allowNonExistentFile'], $options['extension']);

        parent::__construct($options);
    }

    /**
     * Returns true if and only if the file extension of $value is included in the
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

        $extensions = self::listPossibleFileNameExtensions($fileName);

        if (! self::extensionFoundInList($this->extensions, $extensions, $this->caseSensitive)) {
            $this->error(self::FALSE_EXTENSION);

            return false;
        }

        return true;
    }

    /**
     * Parse the `extension` option to a list of non-empty strings
     *
     * @internal
     *
     * @param string|array<array-key, string> $extensions
     * @return non-empty-list<non-empty-string>
     *
     * @psalm-pure
     *
     * @throws InvalidArgumentException If the argument resolves to an empty list.
     */
    public static function resolveExtensionList(string|array $extensions): array
    {
        $extensions = is_string($extensions)
            ? explode(',', $extensions)
            : $extensions;

        $list = [];
        foreach ($extensions as $ext) {
            $ext = trim($ext);
            if ($ext === '') {
                continue;
            }

            $list[] = $ext;
        }

        if ($list === []) {
            throw new InvalidArgumentException('The extension option must resolve to a non-empty list');
        }

        return $list;
    }

    /**
     * Return a list of possible filename extensions based on the filename provided
     *
     * For example, a file name such as `foo.bing.tar.gz` will return the list ['gz', 'tar.gz', 'bing.tar.gz']
     *
     * @internal
     *
     * @param non-empty-string $fileName
     * @return list<non-empty-string>
     *
     * @psalm-pure
     */
    public static function listPossibleFileNameExtensions(string $fileName): array
    {
        $parts = explode('.', $fileName);
        array_shift($parts);
        $list = [];
        foreach (array_reverse($parts) as $part) {
            if ($part === '') {
                continue;
            }

            $ext    = end($list);
            $list[] = $ext !== false ? sprintf('%s.%s', $part, $ext) : $part;
        }

        return $list;
    }

    /**
     * Determine whether any of the `$extensions` are present in `$list`
     *
     * @internal
     *
     * @param non-empty-list<non-empty-string> $list
     * @param list<non-empty-string> $extensions
     *
     * @psalm-pure
     */
    public static function extensionFoundInList(array $list, array $extensions, bool $caseSensitive): bool
    {
        foreach ($extensions as $ext) {
            foreach ($list as $match) {
                if ($caseSensitive && $ext === $match) {
                    return true;
                }

                if (! $caseSensitive && strtolower($ext) === strtolower($match)) {
                    return true;
                }
            }
        }

        return false;
    }
}
