<?php

declare(strict_types=1);

namespace Laminas\Validator;

use function is_float;
use function is_int;
use function is_string;
use function preg_replace;

final class Digits extends AbstractValidator
{
    public const NOT_DIGITS   = 'notDigits';
    public const STRING_EMPTY = 'digitsStringEmpty';
    public const INVALID      = 'digitsInvalid';

    /**
     * Validation failure message template definitions
     *
     * @var array<string, string>
     */
    protected array $messageTemplates = [
        self::NOT_DIGITS   => 'The input must contain only digits',
        self::STRING_EMPTY => 'The input is an empty string',
        self::INVALID      => 'Invalid type given. String, integer or float expected',
    ];

    /**
     * Returns true if and only if $value only contains digit characters
     */
    public function isValid(mixed $value): bool
    {
        if (! is_string($value) && ! is_int($value) && ! is_float($value)) {
            $this->error(self::INVALID);
            return false;
        }

        $this->setValue((string) $value);

        if ('' === $this->getValue()) {
            $this->error(self::STRING_EMPTY);
            return false;
        }

        $digits = preg_replace('/[^0-9]/', '', (string) $value);

        if ((string) $value !== $digits) {
            $this->error(self::NOT_DIGITS);
            return false;
        }

        return true;
    }
}
