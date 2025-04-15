<?php

declare(strict_types=1);

namespace Laminas\Validator\File;

use Laminas\Translator\TranslatorInterface;
use Laminas\Validator\AbstractValidator;
use Laminas\Validator\Exception\InvalidArgumentException;

use function hash_algos;
use function hash_equals;
use function hash_file;
use function in_array;
use function is_string;
use function strtolower;

/**
 * Validator for the hash of given files
 *
 * @psalm-type OptionsArgument = array{
 *     hash: non-empty-string|list<non-empty-string>,
 *     algorithm?: non-empty-string|null,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
final class Hash extends AbstractValidator
{
    public const DOES_NOT_MATCH = 'fileHashDoesNotMatch';
    public const NOT_DETECTED   = 'fileHashHashNotDetected';
    public const NOT_FOUND      = 'fileHashNotFound';

    /** @var array<string, string> */
    protected array $messageTemplates = [
        self::DOES_NOT_MATCH => 'File does not match the given hashes',
        self::NOT_DETECTED   => 'A hash could not be evaluated for the given file',
        self::NOT_FOUND      => 'File is not readable or does not exist',
    ];

    /** @var list<non-empty-string> */
    private readonly array $hash;
    private readonly string $algorithm;

    /**
     * Sets validator options
     *
     * @param OptionsArgument $options
     */
    public function __construct(array $options)
    {
        $hash = $options['hash'] ?? [];
        if (is_string($hash)) {
            $hash = [$hash];
        }

        if ($hash === []) {
            throw new InvalidArgumentException(
                'Files cannot be validated without a hash specified',
            );
        }

        $algorithm = strtolower($options['algorithm'] ?? 'crc32');
        if (! in_array($algorithm, hash_algos(), true)) {
            throw new InvalidArgumentException("Unknown algorithm '{$algorithm}'");
        }

        $this->hash      = $hash;
        $this->algorithm = $algorithm;

        unset($options['hash'], $options['algorithm']);

        parent::__construct($options);
    }

    /**
     * Returns true if and only if the given file confirms the set hash
     */
    public function isValid(mixed $value): bool
    {
        if (! FileInformation::isPossibleFile($value)) {
            $this->error(self::NOT_FOUND);

            return false;
        }

        $file = FileInformation::factory($value);

        if (! $file->readable) {
            $this->error(self::NOT_FOUND);

            return false;
        }

        $this->setValue($file->clientFileName ?? $file->baseName);

        $hash = hash_file($this->algorithm, $file->path);
        if ($hash === false) {
            $this->error(self::NOT_DETECTED);
            return false;
        }

        foreach ($this->hash as $knownHash) {
            if (hash_equals($knownHash, $hash)) {
                return true;
            }
        }

        $this->error(self::DOES_NOT_MATCH);
        return false;
    }
}
