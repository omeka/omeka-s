<?php

declare(strict_types=1);

namespace Laminas\Validator;

use Laminas\Translator\TranslatorInterface;
use Laminas\Validator\Exception\InvalidArgumentException;

use function in_array;
use function is_int;
use function is_string;
use function preg_match;
use function str_replace;
use function strlen;
use function substr;

/**
 * @psalm-type OptionsArgument = array{
 *     type?: Isbn::AUTO|Isbn::ISBN10|Isbn::ISBN13,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
final class Isbn extends AbstractValidator
{
    public const AUTO    = 'auto';
    public const ISBN10  = '10';
    public const ISBN13  = '13';
    public const INVALID = 'isbnInvalid';
    public const NO_ISBN = 'isbnNoIsbn';

    /**
     * Validation failure message template definitions.
     *
     * @var array<string, string>
     */
    protected array $messageTemplates = [
        self::INVALID => 'Invalid type given. String or integer expected',
        self::NO_ISBN => 'The input is not a valid ISBN number',
    ];

    private readonly string $type;

    /**
     * @param OptionsArgument $options
     * @psalm-suppress DocblockTypeContradiction Ignoring runtime value checks.
     */
    public function __construct(array $options = [])
    {
        $type = $options['type'] ?? self::AUTO;

        if (! in_array($type, [self::AUTO, self::ISBN10, self::ISBN13], true)) {
            throw new InvalidArgumentException('Invalid ISBN type');
        }

        $this->type = $type;

        unset($options['type']);

        parent::__construct($options);
    }

    /**
     * Detect input format.
     *
     * @return self::ISBN10|self::ISBN13|null
     */
    private function detectFormat(string $value): ?string
    {
        // check for ISBN-10
        if ($this->type === self::ISBN10 || $this->type === self::AUTO) {
            if (strlen($value) === 10 && preg_match('/^[0-9]{9}[0-9X]$/', $value)) {
                return self::ISBN10;
            }
        }

        // check for ISBN-13
        if ($this->type === self::ISBN13 || $this->type === self::AUTO) {
            if (strlen($value) === 13 && preg_match('/^[0-9]{13}$/', $value)) {
                return self::ISBN13;
            }
        }

        return null;
    }

    /**
     * Returns true if and only if $value is a valid ISBN.
     */
    public function isValid(mixed $value): bool
    {
        if (! is_string($value) && ! is_int($value)) {
            $this->error(self::INVALID);
            return false;
        }

        $value = (string) $value;
        $this->setValue($value);
        // Strip separators from the ISBN prior to validation
        $value = str_replace([' ', '-'], '', $value);

        $type = $this->detectFormat($value);
        if ($type === null) {
            $this->error(self::NO_ISBN);

            return false;
        }

        $checksum = $type === self::ISBN10
        ? $this->calculateIsbn10Checksum($value)
        : $this->calculateIsbn13Checksum($value);

        // validate
        if (substr($value, -1) !== $checksum) {
            $this->error(self::NO_ISBN);
            return false;
        }

        return true;
    }

    private function calculateIsbn10Checksum(string $value): string
    {
        $sum = 0;

        for ($i = 0; $i < 9; $i++) {
            $sum += (10 - $i) * (int) $value[$i];
        }

        $checksum = 11 - ($sum % 11);

        if ($checksum === 11) {
            return '0';
        }

        if ($checksum === 10) {
            return 'X';
        }

        return (string) $checksum;
    }

    private function calculateIsbn13Checksum(string $value): string
    {
        $sum = 0;

        for ($i = 0; $i < 12; $i++) {
            if ($i % 2 === 0) {
                $sum += (int) $value[$i];
                continue;
            }

            $sum += 3 * (int) $value[$i];
        }

        $checksum = 10 - ($sum % 10);

        if ($checksum === 10) {
            return '0';
        }

        return (string) $checksum;
    }
}
