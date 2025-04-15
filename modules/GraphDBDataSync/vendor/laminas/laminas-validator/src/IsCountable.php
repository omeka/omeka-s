<?php

declare(strict_types=1);

namespace Laminas\Validator;

use Laminas\Translator\TranslatorInterface;
use Laminas\Validator\Exception\InvalidArgumentException;

use function count;
use function is_countable;

/**
 * Validate that a value is countable and the count meets expectations.
 *
 * The validator has five specific behaviors:
 *
 * - You can determine if a value is countable only
 * - You can test if the value is an exact count
 * - You can test if the value is greater than a minimum count value
 * - You can test if the value is greater than a maximum count value
 * - You can test if the value is between the minimum and maximum count values
 *
 * When creating the instance if you specify a
 * "count" option, specifying either "min" or "max" leads to an inconsistent
 * state and, as such will raise an InvalidArgumentException.
 *
 * @psalm-type OptionsArgument = array{
 *     count?: int|null,
 *     min?: int|null,
 *     max?: int|null,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
final class IsCountable extends AbstractValidator
{
    public const NOT_COUNTABLE = 'notCountable';
    public const NOT_EQUALS    = 'notEquals';
    public const GREATER_THAN  = 'greaterThan';
    public const LESS_THAN     = 'lessThan';

    /**
     * Validation failure message template definitions
     *
     * @var array<string, string>
     */
    protected array $messageTemplates = [
        self::NOT_COUNTABLE => 'The input must be an array or an instance of \\Countable',
        self::NOT_EQUALS    => "The input count must equal '%count%'",
        self::GREATER_THAN  => "The input count must be less than '%max%', inclusively",
        self::LESS_THAN     => "The input count must be greater than '%min%', inclusively",
    ];

    /** @var array<string, string|array<string, string>> */
    protected array $messageVariables = [
        'count' => 'count',
        'min'   => 'min',
        'max'   => 'max',
    ];

    protected readonly ?int $min;
    protected readonly ?int $max;
    protected readonly ?int $count;

    /** @param OptionsArgument $options */
    public function __construct(array $options = [])
    {
        $min   = $options['min'] ?? null;
        $max   = $options['max'] ?? null;
        $count = $options['count'] ?? null;

        if ($count !== null && ($min !== null || $max !== null)) {
            throw new InvalidArgumentException(
                'The `count` option is mutually exclusive with the `min` and `max` options',
            );
        }

        if ($max !== null && $min !== null && $max < $min) {
            throw new InvalidArgumentException(
                'The `max` option cannot be less than the `min` option',
            );
        }

        $this->min   = $min;
        $this->max   = $max;
        $this->count = $count;

        unset($options['min'], $options['max'], $options['count']);

        parent::__construct($options);
    }

    /**
     * Returns true if and only if $value is countable (and the count validates against optional values).
     */
    public function isValid(mixed $value): bool
    {
        if (! is_countable($value)) {
            $this->error(self::NOT_COUNTABLE);
            return false;
        }

        $count = count($value);

        if ($this->count !== null) {
            if ($count !== $this->count) {
                $this->error(self::NOT_EQUALS);
                return false;
            }

            return true;
        }

        if ($this->max !== null && $count > $this->max) {
            $this->error(self::GREATER_THAN);
            return false;
        }

        if ($this->min !== null && $count < $this->min) {
            $this->error(self::LESS_THAN);
            return false;
        }

        return true;
    }
}
