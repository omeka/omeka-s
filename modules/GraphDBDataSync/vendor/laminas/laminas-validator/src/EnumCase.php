<?php

declare(strict_types=1);

namespace Laminas\Validator;

use BackedEnum;
use Laminas\Translator\TranslatorInterface;
use Laminas\Validator\Exception\InvalidArgumentException;
use UnitEnum;

use function array_map;
use function get_debug_type;
use function in_array;
use function is_a;
use function is_scalar;
use function is_string;
use function sprintf;

/**
 * @psalm-type OptionsArgument = array{
 *     enum: class-string<UnitEnum>|class-string<BackedEnum>,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
final class EnumCase extends AbstractValidator
{
    public const ERR_INVALID_TYPE  = 'errInvalidType';
    public const ERR_INVALID_VALUE = 'errInvalidValue';

    /** @var array<string, string> */
    protected array $messageTemplates = [
        self::ERR_INVALID_TYPE  => 'Expected a string but received %type%',
        self::ERR_INVALID_VALUE => '"%value%" is not a valid enum case',
    ];

    /** @var class-string<UnitEnum>|class-string<BackedEnum> */
    private readonly string $enum;

    protected ?string $type = null;

    /** @var array<string, string|array<string, string>> */
    protected array $messageVariables = [
        'type' => 'type',
    ];

    /** @param OptionsArgument $options */
    public function __construct(array $options)
    {
        $enum = $options['enum'];
        if (! is_a($enum, BackedEnum::class, true) && ! is_a($enum, UnitEnum::class, true)) {
            throw new InvalidArgumentException(sprintf(
                'Expected the `enum` option to be a unit or backed enum class-string but "%s" received',
                $enum,
            ));
        }

        $this->enum = $enum;

        parent::__construct($options);
    }

    public function isValid(mixed $value): bool
    {
        $this->setValue(is_scalar($value) ? (string) $value : get_debug_type($value));
        $this->type = get_debug_type($value);

        if (! is_string($value)) {
            $this->error(self::ERR_INVALID_TYPE);

            return false;
        }

        $match = array_map(
            static fn (UnitEnum|BackedEnum $case): string => $case->name,
            $this->enum::cases(),
        );

        if (! in_array($value, $match, true)) {
            $this->error(self::ERR_INVALID_VALUE);

            return false;
        }

        return true;
    }
}
