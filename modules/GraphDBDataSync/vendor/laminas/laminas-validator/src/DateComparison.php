<?php

declare(strict_types=1);

namespace Laminas\Validator;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Laminas\Translator\TranslatorInterface;
use Laminas\Validator\Exception\InvalidArgumentException;

use function assert;
use function get_debug_type;
use function is_string;
use function preg_match;

/**
 * @psalm-type OptionsArgument = array{
 *     min?: string|DateTimeInterface|null,
 *     max?: string|DateTimeInterface|null,
 *     inclusiveMin?: bool,
 *     inclusiveMax?: bool,
 *     inputFormat?: string|null,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
final class DateComparison extends AbstractValidator
{
    public const ERROR_INVALID_TYPE          = 'invalidType';
    public const ERROR_INVALID_DATE          = 'invalidDate';
    public const ERROR_NOT_GREATER_INCLUSIVE = 'notGreaterInclusive';
    public const ERROR_NOT_GREATER           = 'notGreater';
    public const ERROR_NOT_LESS_INCLUSIVE    = 'notLessInclusive';
    public const ERROR_NOT_LESS              = 'notLess';

    /** @var array<string, string> */
    protected array $messageTemplates = [
        self::ERROR_INVALID_TYPE          => 'Expected a string or a date time instance but received "%type"',
        self::ERROR_INVALID_DATE          => 'Invalid date provided',
        self::ERROR_NOT_GREATER_INCLUSIVE => 'A date equal to or after %min% is required',
        self::ERROR_NOT_GREATER           => 'A date after %min% is required',
        self::ERROR_NOT_LESS_INCLUSIVE    => 'A date equal to or before %max% is required',
        self::ERROR_NOT_LESS              => 'A date before %max% is required',
    ];

    /** @var array<string, string|array<string, string>> */
    protected array $messageVariables = [
        'type' => 'type',
        'min'  => 'minString',
        'max'  => 'maxString',
    ];

    private readonly ?DateTimeInterface $min;
    private readonly ?DateTimeInterface $max;
    private readonly bool $inclusiveMin;
    private readonly bool $inclusiveMax;
    private readonly ?string $inputFormat;

    /** Input type used in message variables */
    protected ?string $type      = null;
    protected ?string $minString = null;
    protected ?string $maxString = null;

    /** @param OptionsArgument $options */
    public function __construct(array $options = [])
    {
        parent::__construct($options);

        $this->min          = $this->dateInstanceBound($options['min'] ?? null);
        $this->max          = $this->dateInstanceBound($options['max'] ?? null);
        $this->inclusiveMin = $options['inclusiveMin'] ?? true;
        $this->inclusiveMax = $options['inclusiveMax'] ?? true;
        $this->inputFormat  = $options['inputFormat'] ?? null;

        if ($this->min === null && $this->max === null) {
            throw new InvalidArgumentException(
                'At least one date boundary must be supplied',
            );
        }

        $outputFormat = $this->inputFormat ?? 'jS F Y H:i:s';

        if ($this->min !== null) {
            $this->minString = $this->min->format($outputFormat);
        }

        if ($this->max !== null) {
            $this->maxString = $this->max->format($outputFormat);
        }
    }

    public function isValid(mixed $value): bool
    {
        $this->type = get_debug_type($value);
        $this->setValue($value);

        if (! is_string($value) && ! $value instanceof DateTimeInterface) {
            $this->error(self::ERROR_INVALID_TYPE);

            return false;
        }

        $date = $this->valueToDate($value);
        if ($date === null) {
            $this->error(self::ERROR_INVALID_DATE);

            return false;
        }

        if ($this->min !== null && $this->inclusiveMin && $date < $this->min) {
            $this->error(self::ERROR_NOT_GREATER_INCLUSIVE);

            return false;
        }

        if ($this->min !== null && ! $this->inclusiveMin && $date <= $this->min) {
            $this->error(self::ERROR_NOT_GREATER);

            return false;
        }

        if ($this->max !== null && $this->inclusiveMax && $date > $this->max) {
            $this->error(self::ERROR_NOT_LESS_INCLUSIVE);

            return false;
        }

        if ($this->max !== null && ! $this->inclusiveMax && $date >= $this->max) {
            $this->error(self::ERROR_NOT_LESS);

            return false;
        }

        return true;
    }

    private function valueToDate(string|DateTimeInterface $input): DateTimeInterface|null
    {
        if ($input instanceof DateTimeInterface) {
            return $this->w3cDateFromString($input->format('Y-m-d\TH:i:s'));
        }

        if ($this->inputFormat !== null) {
            $date = DateTimeImmutable::createFromFormat($this->inputFormat, $input, new DateTimeZone('UTC'));

            if ($date instanceof DateTimeImmutable) {
                return $date;
            }
        }

        $date = $this->isoDateFromString($input);
        if ($date !== null) {
            return $date;
        }

        $date = $this->w3cDateFromString($input);
        if ($date !== null) {
            return $date;
        }

        return null;
    }

    private function dateInstanceBound(string|DateTimeInterface|null $dateTime): DateTimeInterface|null
    {
        if ($dateTime instanceof DateTimeInterface) {
            return $this->w3cDateFromString($dateTime->format('Y-m-d\TH:i:s'));
        }

        if ($dateTime === null) {
            return null;
        }

        $date = $this->isoDateFromString($dateTime);
        if ($date !== null) {
            return $date;
        }

        $date = $this->w3cDateFromString($dateTime);
        if ($date !== null) {
            return $date;
        }

        throw new InvalidArgumentException(
            'Min/max date bounds must be either DateTime instances, or a string in one of the formats: '
            . '"Y-m-d" for a date or "Y-m-d\TH:i:s" for date time',
        );
    }

    private function isoDateFromString(string $input): DateTimeImmutable|null
    {
        if (! preg_match('/^\d{4}-[0-1]\d-[0-3]\d$/', $input)) {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $input, new DateTimeZone('UTC'));
        assert($date !== false);

        return $date;
    }

    private function w3cDateFromString(string $input): DateTimeImmutable|null
    {
        if (! preg_match('/^\d{4}-[0-1]\d-[0-3]\dT\d{1,2}:[0-5]\d:[0-5]\d$/', $input)) {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s', $input, new DateTimeZone('UTC'));
        assert($date !== false);

        return $date;
    }
}
