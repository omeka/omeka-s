<?php

declare(strict_types=1);

namespace Laminas\Validator;

use Laminas\Translator\TranslatorInterface;

use function is_float;
use function is_numeric;

/**
 * @psalm-type OptionsArgument = array{
 *     operator?: Bitwise::OP_AND|Bitwise::OP_XOR|null,
 *     control: int,
 *     strict?: bool,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
final class Bitwise extends AbstractValidator
{
    public const OP_AND = 'and';
    public const OP_XOR = 'xor';

    public const NOT_AND        = 'notAnd';
    public const NOT_AND_STRICT = 'notAndStrict';
    public const NOT_XOR        = 'notXor';
    public const NO_OP          = 'noOp';
    public const NOT_INTEGER    = 'notInteger';

    /**
     * Validation failure message template definitions
     *
     * @var array<string, string>
     */
    protected array $messageTemplates = [
        self::NOT_AND        => "The input has no common bit set with '%control%'",
        self::NOT_AND_STRICT => "The input doesn't have the same bits set as '%control%'",
        self::NOT_XOR        => "The input has common bit set with '%control%'",
        self::NO_OP          => "No operator was present to compare '%control%' against",
        self::NOT_INTEGER    => "Expected an integer to compare '%control%' against",
    ];

    /** @var array<string, string|array<string, string>> */
    protected array $messageVariables = [
        'control' => 'control',
    ];

    /** @var self::OP_AND|self::OP_XOR|null */
    private readonly ?string $operator;
    private readonly bool $strict;
    protected readonly int $control;

    /** @param OptionsArgument $options */
    public function __construct(array $options)
    {
        $this->control  = $options['control'];
        $this->operator = $options['operator'] ?? null;
        $this->strict   = $options['strict'] ?? false;

        parent::__construct($options);
    }

    public function isValid(mixed $value): bool
    {
        $this->setValue($value);

        if (! is_numeric($value) || is_float($value)) {
            $this->error(self::NOT_INTEGER);

            return false;
        }

        $value = (int) $value;

        if (self::OP_AND === $this->operator) {
            if ($this->strict) {
                // All the bits set in value must be set in control
                $result = ($this->control & $value) === $value;

                if (! $result) {
                    $this->error(self::NOT_AND_STRICT);
                }

                return $result;
            }

            // At least one of the bits must be common between value and control
            $result = (bool) ($this->control & $value);

            if (! $result) {
                $this->error(self::NOT_AND);
            }

            return $result;
        }

        if (self::OP_XOR === $this->operator) {
            // Parentheses are required due to order of operations with bitwise operations
            // phpcs:ignore WebimpressCodingStandard.Formatting.RedundantParentheses.SingleEquality
            $result = ($this->control ^ $value) === ($this->control | $value);

            if (! $result) {
                $this->error(self::NOT_XOR);
            }

            return $result;
        }

        $this->error(self::NO_OP);
        return false;
    }
}
