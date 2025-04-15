<?php

declare(strict_types=1);

namespace Laminas\Validator;

use Laminas\Translator\TranslatorInterface;
use Laminas\Validator\Exception\InvalidArgumentException;

use function is_numeric;

/**
 * @psalm-type OptionsArgument = array{
 *     min?: numeric|null,
 *     max?: numeric|null,
 *     inclusiveMin?: bool,
 *     inclusiveMax?: bool,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
final class NumberComparison extends AbstractValidator
{
    public const ERROR_NOT_NUMERIC           = 'notNumeric';
    public const ERROR_NOT_GREATER_INCLUSIVE = 'notGreaterInclusive';
    public const ERROR_NOT_GREATER           = 'notGreater';
    public const ERROR_NOT_LESS_INCLUSIVE    = 'notLessInclusive';
    public const ERROR_NOT_LESS              = 'notLess';

    /** @var array<string, string> */
    protected array $messageTemplates = [
        self::ERROR_NOT_NUMERIC           => 'Expected a numeric value',
        self::ERROR_NOT_GREATER_INCLUSIVE => 'Values must be greater than or equal to %min%. Received "%value%"',
        self::ERROR_NOT_GREATER           => 'Values must be greater than %min%. Received "%value%',
        self::ERROR_NOT_LESS_INCLUSIVE    => 'Values must be less than or equal to %max%. Received "%value%"',
        self::ERROR_NOT_LESS              => 'Values must be less than %max%. Received "%value%"',
    ];

    /** @var array<string, string|array<string, string>> */
    protected array $messageVariables = [
        'min' => 'min',
        'max' => 'max',
    ];

    /** @var numeric|null */
    protected readonly int|float|string|null $min;
    /** @var numeric|null */
    protected readonly int|float|string|null $max;
    private readonly bool $inclusiveMin;
    private readonly bool $inclusiveMax;

    /** @param OptionsArgument $options */
    public function __construct(array $options = [])
    {
        $min = $options['min'] ?? null;
        $max = $options['max'] ?? null;

        if (! is_numeric($min) && ! is_numeric($max)) {
            throw new InvalidArgumentException(
                'A numeric option value for either min, max or both must be provided',
            );
        }

        if ($min !== null && $max !== null && $min > $max) {
            throw new InvalidArgumentException(
                'The minimum constraint cannot be greater than the maximum constraint',
            );
        }

        $this->min          = $min;
        $this->max          = $max;
        $this->inclusiveMin = $options['inclusiveMin'] ?? true;
        $this->inclusiveMax = $options['inclusiveMax'] ?? true;

        unset(
            $options['min'],
            $options['max'],
            $options['inclusiveMin'],
            $options['inclusiveMax'],
        );

        parent::__construct($options);
    }

    public function isValid(mixed $value): bool
    {
        if (! is_numeric($value)) {
            $this->error(self::ERROR_NOT_NUMERIC);

            return false;
        }

        $this->setValue($value);

        if ($this->min !== null && $this->inclusiveMin && $value < $this->min) {
            $this->error(self::ERROR_NOT_GREATER_INCLUSIVE);

            return false;
        }

        if ($this->min !== null && ! $this->inclusiveMin && $value <= $this->min) {
            $this->error(self::ERROR_NOT_GREATER);

            return false;
        }

        if ($this->max !== null && $this->inclusiveMax && $value > $this->max) {
            $this->error(self::ERROR_NOT_LESS_INCLUSIVE);

            return false;
        }

        if ($this->max !== null && ! $this->inclusiveMax && $value >= $this->max) {
            $this->error(self::ERROR_NOT_LESS);

            return false;
        }

        return true;
    }
}
