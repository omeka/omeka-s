<?php

declare(strict_types=1);

namespace Laminas\Validator;

use function is_string;
use function preg_match;

/**
 * Uuid validator.
 */
final class Uuid extends AbstractValidator
{
    /**
     * Matches Uuid's versions 1 to 7.
     */
    private const REGEX_UUID = '/^[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}$/';

    public const INVALID    = 'valueNotUuid';
    public const NOT_STRING = 'valueNotString';

    /** @var array<string, string> */
    protected array $messageTemplates = [
        self::NOT_STRING => 'Invalid type given; string expected',
        self::INVALID    => 'Invalid UUID format',
    ];

    /**
     * Returns true if and only if $value meets the validation requirements.
     *
     * If $value fails validation, then this method returns false, and
     * getMessages() will return an array of messages that explain why the
     * validation failed.
     */
    public function isValid(mixed $value): bool
    {
        if (! is_string($value)) {
            $this->error(self::NOT_STRING);
            return false;
        }

        $this->setValue($value);

        if ($value === '00000000-0000-0000-0000-000000000000') {
            return true;
        }

        if (
            $value === ''
            || ! preg_match(self::REGEX_UUID, $value)
        ) {
            $this->error(self::INVALID);
            return false;
        }

        return true;
    }
}
