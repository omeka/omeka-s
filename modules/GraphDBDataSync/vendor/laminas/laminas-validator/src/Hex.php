<?php

declare(strict_types=1);

namespace Laminas\Validator;

use function ctype_xdigit;
use function is_int;
use function is_string;

final class Hex extends AbstractValidator
{
    public const INVALID = 'hexInvalid';
    public const NOT_HEX = 'notHex';

    /**
     * Validation failure message template definitions
     *
     * @var array<string, string>
     */
    protected array $messageTemplates = [
        self::INVALID => 'Invalid type given. String expected',
        self::NOT_HEX => 'The input contains non-hexadecimal characters',
    ];

    /**
     * Returns true if and only if $value contains only hexadecimal digit characters
     */
    public function isValid(mixed $value): bool
    {
        if (! is_string($value) && ! is_int($value)) {
            $this->error(self::INVALID);
            return false;
        }

        $this->setValue($value);
        if (! ctype_xdigit((string) $value)) {
            $this->error(self::NOT_HEX);
            return false;
        }

        return true;
    }
}
