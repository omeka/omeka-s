<?php

declare(strict_types=1);

namespace Laminas\Validator;

use BackedEnum;
use Laminas\Translator\TranslatorInterface;
use Laminas\Validator\Exception\InvalidArgumentException;
use ReflectionEnum;
use ReflectionNamedType;

use function assert;
use function get_debug_type;
use function is_a;
use function is_int;
use function is_scalar;
use function is_string;
use function sprintf;

/**
 * @psalm-type OptionsArgument = array{
 *     enum: class-string<BackedEnum>,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
final class BackedEnumValue extends AbstractValidator
{
    public const ERR_INVALID_TYPE  = 'errInvalidType';
    public const ERR_INVALID_VALUE = 'errInvalidValue';

    /** @var array<string, string> */
    protected array $messageTemplates = [
        self::ERR_INVALID_TYPE  => 'Expected a string or numeric value but received %type%',
        self::ERR_INVALID_VALUE => '"%value%" is not a valid enum case',
    ];

    /** @var class-string<BackedEnum> */
    private readonly string $enum;
    private readonly bool $isStringBacked;

    protected ?string $type = null;

    /** @var array<string, string|array<string, string>> */
    protected array $messageVariables = [
        'type' => 'type',
    ];

    /** @param OptionsArgument $options */
    public function __construct(array $options)
    {
        $enum = $options['enum'];
        if (! is_a($enum, BackedEnum::class, true)) {
            throw new InvalidArgumentException(sprintf(
                'Expected the `enum` option to be a backed enum class-string but "%s" received',
                $enum,
            ));
        }

        $this->enum = $enum;

        $reflection     = new ReflectionEnum($this->enum);
        $reflectionType = $reflection->getBackingType();
        assert($reflectionType instanceof ReflectionNamedType);
        $this->isStringBacked = $reflectionType->getName() === 'string';

        parent::__construct($options);
    }

    public function isValid(mixed $value): bool
    {
        $this->setValue(is_scalar($value) ? (string) $value : get_debug_type($value));
        $this->type = get_debug_type($value);

        if (! is_string($value) && ! is_int($value)) {
            $this->error(self::ERR_INVALID_TYPE);

            return false;
        }

        if ($this->isStringBacked && ! is_string($value)) {
            $this->error(self::ERR_INVALID_TYPE);

            return false;
        }

        if (! $this->isStringBacked && (string) (int) $value !== (string) $value) {
            $this->error(self::ERR_INVALID_TYPE);

            return false;
        }

        $value = $this->isStringBacked ? $value : (int) $value;

        $enum = $this->enum::tryFrom($value);
        if ($enum === null) {
            $this->error(self::ERR_INVALID_VALUE);

            return false;
        }

        return true;
    }
}
