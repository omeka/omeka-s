<?php

declare(strict_types=1);

namespace Laminas\Validator;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Laminas\Translator\TranslatorInterface;

use function gettype;
use function implode;

/**
 * Validates that a given value is a DateTimeInterface instance or can be converted into one.
 *
 * @psalm-type OptionsArgument = array{
 *     format?: string|null,
 *     strict?: bool,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
class Date extends AbstractValidator
{
    public const INVALID      = 'dateInvalid';
    public const INVALID_DATE = 'dateInvalidDate';
    public const FALSEFORMAT  = 'dateFalseFormat';

    /**
     * Default format constant
     */
    public const FORMAT_DEFAULT = 'Y-m-d';

    /**
     * Validation failure message template definitions
     *
     * @var array<string, string>
     */
    protected array $messageTemplates = [
        self::INVALID      => 'Invalid type given. String, integer, array or DateTime expected',
        self::INVALID_DATE => 'The input does not appear to be a valid date',
        self::FALSEFORMAT  => "The input does not fit the date format '%format%'",
    ];

    /** @var array<string, string|array<string, string>> */
    protected array $messageVariables = [
        'format' => 'format',
    ];

    protected readonly string $format;
    protected readonly bool $strict;

    /**
     * Sets validator options
     *
     * @param OptionsArgument $options
     */
    public function __construct(array $options = [])
    {
        $this->format = $options['format'] ?? self::FORMAT_DEFAULT;
        $this->strict = $options['strict'] ?? false;

        unset($options['format'], $options['strict']);

        parent::__construct($options);
    }

    /**
     * Returns true if $value is a DateTimeInterface instance or can be converted into one.
     */
    public function isValid(mixed $value): bool
    {
        $this->setValue($value);

        $date = $this->convertToDateTime($value);
        if (! $date instanceof DateTimeInterface) {
            $this->error(self::INVALID_DATE);
            return false;
        }

        if ($this->strict && $date->format($this->format) !== $value) {
            $this->error(self::FALSEFORMAT);
            return false;
        }

        return true;
    }

    /**
     * Attempts to convert an int, string, or array to a DateTime object
     */
    protected function convertToDateTime(mixed $param, bool $addErrors = true): DateTimeInterface|false
    {
        if ($param instanceof DateTimeInterface) {
            return $param;
        }

        $type = gettype($param);
        switch ($type) {
            case 'string':
                return $this->convertString($param, $addErrors);
            case 'integer':
            case 'double':
                return $this->convertNumeric($param, $addErrors);
            case 'array':
                return $this->convertArray($param, $addErrors);
        }

        if ($addErrors) {
            $this->error(self::INVALID);
        }

        return false;
    }

    /**
     * Attempts to convert an integer into a DateTime object
     */
    private function convertNumeric(int|float $value, bool $addErrors = true): DateTimeImmutable|false
    {
        $date = DateTimeImmutable::createFromFormat('U', (string) $value);
        if ($date === false && $addErrors) {
            $this->error(self::INVALID_DATE);
        }

        return $date;
    }

    /**
     * Attempts to convert a string into a DateTime object
     */
    protected function convertString(string $value, bool $addErrors = true): DateTimeImmutable|false
    {
        $date = DateTimeImmutable::createFromFormat($this->format, $value);

        // Invalid dates can show up as warnings (ie. "2007-02-99")
        // and still return a DateTime object.
        $errors = DateTime::getLastErrors();
        if ($errors === false) {
            return $date;
        }

        if ($errors['warning_count'] > 0) {
            if ($addErrors) {
                $this->error(self::FALSEFORMAT);
            }
            return false;
        }

        return $date;
    }

    /**
     * Implodes the array into a string and proxies to {@link convertString()}.
     */
    private function convertArray(array $value, bool $addErrors = true): DateTimeImmutable|false
    {
        return $this->convertString(implode('-', $value), $addErrors);
    }
}
