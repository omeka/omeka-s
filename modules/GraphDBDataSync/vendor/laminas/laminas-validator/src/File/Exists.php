<?php

declare(strict_types=1);

namespace Laminas\Validator\File;

use Laminas\Translator\TranslatorInterface;
use Laminas\Validator\AbstractValidator;

use function array_filter;
use function array_map;
use function array_values;
use function count;
use function explode;
use function file_exists;
use function implode;
use function is_string;
use function ltrim;
use function rtrim;
use function sprintf;
use function trim;

use const DIRECTORY_SEPARATOR;

/**
 * Validator which checks if the file already exists in the directory
 *
 * @psalm-type OptionsArgument = array{
 *     directory?: string|list<string>,
 *     all?: bool,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
final class Exists extends AbstractValidator
{
    public const DOES_NOT_EXIST = 'fileExistsDoesNotExist';

    /** @var array<string, string> */
    protected array $messageTemplates = [
        self::DOES_NOT_EXIST => 'File does not exist',
    ];

    /** @var array<string, string|array<string, string>> */
    protected array $messageVariables = [
        'directory' => 'directoriesAsString',
    ];

    protected readonly string $directoriesAsString;

    /** @var list<string> */
    private readonly array $directories;
    private readonly bool $all;

    /**
     * Sets validator options
     *
     * @param OptionsArgument $options
     */
    public function __construct(array $options = [])
    {
        $this->directories         = $this->resolveDirectories($options['directory'] ?? null);
        $this->directoriesAsString = implode(', ', $this->directories);
        $this->all                 = $options['all'] ?? true;

        parent::__construct($options);
    }

    /**
     * Returns true if and only if the file already exists in the set directories
     */
    public function isValid(mixed $value): bool
    {
        if (FileInformation::isPossibleFile($value)) {
            $file = FileInformation::factory($value);
            if ($this->directories === []) {
                return true;
            }

            $value = $file->baseName;
        }

        $this->setValue($value);

        if (! is_string($value)) {
            $this->error(self::DOES_NOT_EXIST);

            return false;
        }

        $count = 0;

        foreach ($this->directories as $directory) {
            $path = sprintf(
                '%s%s%s',
                rtrim($directory, DIRECTORY_SEPARATOR),
                DIRECTORY_SEPARATOR,
                ltrim($value, DIRECTORY_SEPARATOR),
            );

            if (file_exists($path)) {
                $count++;
            }
        }

        if ($this->all === false && $count > 0 || ($count === count($this->directories) && $this->directories !== [])) {
            return true;
        }

        $this->error(self::DOES_NOT_EXIST);

        return false;
    }

    /** @return list<string> */
    private function resolveDirectories(string|array|null $directories): array
    {
        if ($directories === null || $directories === [] || $directories === '') {
            return [];
        }

        if (is_string($directories)) {
            $directories = explode(',', $directories);
        }

        return array_values(
            array_filter(
                array_map(static fn(string $directory): string
                => trim($directory), $directories)
            )
        );
    }
}
