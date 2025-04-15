<?php

declare(strict_types=1);

namespace Laminas\Validator;

use DateTimeZone;
use Laminas\Translator\TranslatorInterface;
use Laminas\Validator\Exception\InvalidArgumentException;

use function array_key_exists;
use function in_array;
use function is_int;
use function is_string;
use function strtolower;

/**
 * @psalm-type OptionsArgument = array{
 *     type?: int-mask<Timezone::LOCATION,Timezone::ABBREVIATION,Timezone::ALL>,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
final class Timezone extends AbstractValidator
{
    public const INVALID                       = 'invalidTimezone';
    public const INVALID_TIMEZONE_LOCATION     = 'invalidTimezoneLocation';
    public const INVALID_TIMEZONE_ABBREVIATION = 'invalidTimezoneAbbreviation';

    public const LOCATION     = 0b01;
    public const ABBREVIATION = 0b10;
    public const ALL          = 0b11;

    /** @var array<string, string> */
    protected array $messageTemplates = [
        self::INVALID                       => 'Invalid timezone given.',
        self::INVALID_TIMEZONE_LOCATION     => 'Invalid timezone location given.',
        self::INVALID_TIMEZONE_ABBREVIATION => 'Invalid timezone abbreviation given.',
    ];

    /** @var int-mask<self::LOCATION,self::ABBREVIATION> */
    private readonly int $type;

    /**
     * @param OptionsArgument $options
     */
    public function __construct(array $options = [])
    {
        $type = $options['type'] ?? self::ALL;

        /** @psalm-suppress DocblockTypeContradiction - This is a defensive check */
        if (! is_int($type) || ($type & self::ALL) === 0) {
            throw new InvalidArgumentException(
                'The type option must be an int-mask of the type constants',
            );
        }

        $this->type = $type;

        parent::__construct($options);
    }

    /**
     * Returns true if timezone location or timezone abbreviations is correct.
     */
    public function isValid(mixed $value): bool
    {
        if (! is_string($value) || $value === '') {
            $this->error(self::INVALID);
            return false;
        }

        $this->setValue($value);

        if ($this->type === self::ALL) {
            if (
                ! array_key_exists(strtolower($value), DateTimeZone::listAbbreviations())
                && ! in_array($value, DateTimeZone::listIdentifiers(), true)
            ) {
                $this->error(self::INVALID);

                return false;
            }
        }

        if ($this->type === self::LOCATION && ! in_array($value, DateTimeZone::listIdentifiers(), true)) {
            $this->error(self::INVALID_TIMEZONE_LOCATION);

            return false;
        }

        if (
            $this->type === self::ABBREVIATION
            && ! array_key_exists(strtolower($value), DateTimeZone::listAbbreviations())
        ) {
            $this->error(self::INVALID_TIMEZONE_ABBREVIATION);

            return false;
        }

        return true;
    }
}
