<?php

declare(strict_types=1);

namespace Laminas\Validator;

use DateInterval;
use Throwable;

use function get_debug_type;
use function is_string;

final class DateIntervalString extends AbstractValidator
{
    public const ERR_NOT_STRING = 'errorNotString';
    public const ERR_INVALID    = 'errorInvalid';

    /** @var array<string, string> */
    protected array $messageTemplates = [
        self::ERR_NOT_STRING => 'Expected a string value but %type% provided',
        self::ERR_INVALID    => 'Invalid date interval specification "%value%',
    ];

    protected ?string $type = null;

    /** @var array<string, string|array<string, string>> */
    protected array $messageVariables = [
        'type' => 'type',
    ];

    public function isValid(mixed $value): bool
    {
        $this->setValue($value);
        $this->type = get_debug_type($value);

        if (! is_string($value)) {
            $this->error(self::ERR_NOT_STRING);

            return false;
        }

        try {
            new DateInterval($value);
        } catch (Throwable) {
            $this->error(self::ERR_INVALID);

            return false;
        }

        return true;
    }
}
