<?php

declare(strict_types=1);

namespace Laminas\Validator;

use Laminas\Translator\TranslatorInterface;

use function is_array;
use function is_int;
use function is_string;
use function key;
use function var_export;

/**
 * @psalm-type OptionsArgument = array{
 *     token?: mixed,
 *     strict?: bool,
 *     literal?: bool,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
final class Identical extends AbstractValidator
{
    public const NOT_SAME      = 'notSame';
    public const MISSING_TOKEN = 'missingToken';

    /** @var array<string, string> */
    protected array $messageTemplates = [
        self::NOT_SAME      => 'The two given tokens do not match',
        self::MISSING_TOKEN => 'No token was provided to match against',
    ];

    /** @var array<string, string|array<string, string>> */
    protected array $messageVariables = [
        'token' => 'tokenString',
    ];

    protected readonly ?string $tokenString;
    private readonly mixed $token;
    private readonly bool $strict;
    private readonly bool $literal;

    /**
     * Sets validator options
     *
     * @param OptionsArgument $options
     */
    public function __construct(array $options = [])
    {
        /** @psalm-suppress MixedAssignment $token */
        $token   = $options['token'] ?? null;
        $strict  = $options['strict'] ?? true;
        $literal = $options['literal'] ?? false;
        unset($options['token'], $options['strict'], $options['literal']);

        $this->token       = $token;
        $this->tokenString = is_array($token) ? var_export($token, true) : (string) $token;
        $this->strict      = $strict;
        $this->literal     = $literal;

        parent::__construct($options);
    }

    /**
     * Returns true if and only if a token has been set and the provided value
     * matches that token.
     *
     * @psalm-suppress MixedAssignment, MixedArrayAccess Tokens are mixed as are array members
     */
    public function isValid(mixed $value, ?array $context = null): bool
    {
        $this->setValue($value);

        if ($this->token === null) {
            $this->error(self::MISSING_TOKEN);
            return false;
        }

        $matchTo = $this->token;

        if (! $this->literal && $context !== null) {
            if (is_array($matchTo)) {
                while (is_array($matchTo)) {
                    $key = key($matchTo);
                    if ($key === null || ! isset($context[$key])) {
                        break;
                    }
                    $context = $context[$key];
                    $matchTo = $matchTo[$key];
                }
            }

            // if $matchTo is an array it means the above loop didn't go all the way down to the leaf,
            // so the $matchTo structure doesn't match the $context structure
            if (
                is_array($matchTo)
                || (! is_int($matchTo) && ! is_string($matchTo))
                || ! isset($context[$matchTo])
            ) {
                $matchTo = $this->token;
            } else {
                $matchTo = $context[$matchTo] ?? null;
            }
        }

        if (
            ($this->strict && ($value !== $matchTo))
        // phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedNotEqualOperator
            || (! $this->strict && ($value != $matchTo))
        ) {
            $this->error(self::NOT_SAME);
            return false;
        }

        return true;
    }
}
