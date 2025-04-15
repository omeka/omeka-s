<?php

declare(strict_types=1);

namespace Laminas\Validator;

use Laminas\Translator\TranslatorInterface;
use Laminas\Validator\Exception\InvalidArgumentException;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;

use function in_array;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;

/**
 * @psalm-type OptionsArgument = array{
 *     haystack: array,
 *     strict?: bool|InArray::COMPARE_*,
 *     recursive?: bool,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
final class InArray extends AbstractValidator
{
    public const NOT_IN_ARRAY = 'notInArray';

    // Type of Strict check
    /**
     * standard in_array strict checking value and type
     */
    public const COMPARE_STRICT = 1;

    /**
     * Non strict check but prevents "asdf" == 0 returning TRUE causing false/positive.
     * This is the most secure option for non-strict checks and replaces strict = false
     * This will only be effective when the input is a string
     */
    public const COMPARE_NOT_STRICT_AND_PREVENT_STR_TO_INT_VULNERABILITY = 0;

    /**
     * Standard non-strict check where "asdf" == 0 returns TRUE
     * This will be wanted when comparing "0" against int 0
     */
    public const COMPARE_NOT_STRICT = -1;

    /** @var array<string, string> */
    protected array $messageTemplates = [
        self::NOT_IN_ARRAY => 'The input was not found in the haystack',
    ];

    /**
     * Haystack of possible values
     *
     * @var array<array-key, mixed>
     */
    private readonly array $haystack;

    /**
     * Type of strict check to be used. Due to "foo" == 0 === TRUE with in_array when strict = false,
     * an option has been added to prevent this. When $strict = 0/false, the most
     * secure non-strict check is implemented. if $strict = -1, the default in_array non-strict
     * behaviour is used
     */
    private readonly int $strict;

    /**
     * Whether a recursive search should be done
     */
    private readonly bool $recursive;

    /** @param OptionsArgument $options */
    public function __construct(array $options)
    {
        $haystack  = $options['haystack'] ?? null;
        $strict    = $options['strict'] ?? self::COMPARE_NOT_STRICT_AND_PREVENT_STR_TO_INT_VULNERABILITY;
        $recursive = $options['recursive'] ?? false;

        unset($options['haystack'], $options['strict'], $options['recursive']);

        if (! is_array($haystack)) {
            throw new InvalidArgumentException('haystack option is mandatory and must be an array');
        }

        if (is_bool($strict)) {
            $strict = $strict
                ? self::COMPARE_STRICT
                : self::COMPARE_NOT_STRICT_AND_PREVENT_STR_TO_INT_VULNERABILITY;
        }

        $this->haystack  = $haystack;
        $this->strict    = $strict;
        $this->recursive = $recursive;

        parent::__construct($options);
    }

    /**
     * Returns true if and only if $value is contained in the haystack option. If the strict
     * option is true, then the type of $value is also checked.
     *
     * See {@link http://php.net/manual/function.in-array.php#104501}
     */
    public function isValid(mixed $value): bool
    {
        // we create a copy of the haystack in case we need to modify it
        $haystack = $this->haystack;

        // if the input is a string or float, and vulnerability protection is on
        // we type cast the input to a string
        if (
            self::COMPARE_NOT_STRICT_AND_PREVENT_STR_TO_INT_VULNERABILITY === $this->strict
            && (is_int($value) || is_float($value))
        ) {
            $value = (string) $value;
        }

        $this->setValue($value);

        if ($this->recursive) {
            $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($haystack));
            /** @psalm-suppress MixedAssignment $element */
            foreach ($iterator as $element) {
                if (self::COMPARE_STRICT === $this->strict) {
                    if ($element === $value) {
                        return true;
                    }

                    continue;
                }

                // add protection to prevent string to int vuln's
                $el = $element;
                if (
                    self::COMPARE_NOT_STRICT_AND_PREVENT_STR_TO_INT_VULNERABILITY === $this->strict
                    && is_string($value) && (is_int($el) || is_float($el))
                ) {
                    $el = (string) $el;
                }

                // phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedEqualOperator
                if ($el == $value) {
                    return true;
                }
            }

            $this->error(self::NOT_IN_ARRAY);
            return false;
        }

        /**
         * If the check is not strict, then, to prevent "asdf" being converted to 0
         * and returning a false positive if 0 is in haystack, we type cast
         * the haystack to strings. To prevent "56asdf" == 56 === TRUE we also
         * type cast values like 56 to strings as well.
         *
         * This occurs only if the input is a string and a haystack member is an int
         */
        if (
            self::COMPARE_NOT_STRICT_AND_PREVENT_STR_TO_INT_VULNERABILITY === $this->strict
            && is_string($value)
        ) {
            /** @psalm-suppress MixedAssignment */
            foreach ($haystack as &$h) {
                if (is_int($h) || is_float($h)) {
                    $h = (string) $h;
                }
            }

            if (in_array($value, $haystack, (bool) $this->strict)) {
                return true;
            }

            $this->error(self::NOT_IN_ARRAY);
            return false;
        }

        if (in_array($value, $haystack, self::COMPARE_STRICT === $this->strict)) {
            return true;
        }

        if (self::COMPARE_NOT_STRICT === $this->strict) {
            return true;
        }

        $this->error(self::NOT_IN_ARRAY);
        return false;
    }
}
