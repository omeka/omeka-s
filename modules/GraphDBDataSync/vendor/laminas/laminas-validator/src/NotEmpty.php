<?php

declare(strict_types=1);

namespace Laminas\Validator;

use Countable;
use Laminas\Translator\TranslatorInterface;

use function array_search;
use function assert;
use function count;
use function in_array;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_object;
use function is_string;
use function method_exists;
use function preg_match;

/**
 * phpcs:disable Generic.Files.LineLength
 *
 * @psalm-type TypeIntMask = int-mask<
 *     NotEmpty::BOOLEAN,
 *     NotEmpty::INTEGER,
 *     NotEmpty::FLOAT,
 *     NotEmpty::STRING,
 *     NotEmpty::ZERO,
 *     NotEmpty::EMPTY_ARRAY,
 *     NotEmpty::NULL,
 *     NotEmpty::SPACE,
 *     NotEmpty::OBJECT,
 *     NotEmpty::OBJECT_STRING,
 *     NotEmpty::OBJECT_COUNT
 * >
 * @psalm-type TypeArgument = TypeIntMask | list<TypeIntMask> | list<value-of<NotEmpty::TYPE_NAMES>> | value-of<NotEmpty::TYPE_NAMES>
 * @psalm-type OptionsArgument = array{
 *     type?: TypeArgument,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
final class NotEmpty extends AbstractValidator
{
    public const BOOLEAN       = 0b000000000001;
    public const INTEGER       = 0b000000000010;
    public const FLOAT         = 0b000000000100;
    public const STRING        = 0b000000001000;
    public const ZERO          = 0b000000010000;
    public const EMPTY_ARRAY   = 0b000000100000;
    public const NULL          = 0b000001000000;
    public const PHP           = 0b000001111111;
    public const SPACE         = 0b000010000000;
    public const OBJECT        = 0b000100000000;
    public const OBJECT_STRING = 0b001000000000;
    public const OBJECT_COUNT  = 0b010000000000;
    public const ALL           = 0b011111111111;

    public const INVALID  = 'notEmptyInvalid';
    public const IS_EMPTY = 'isEmpty';

    private const DEFAULT_TYPE = self::OBJECT
        | self::SPACE
        | self::NULL
        | self::EMPTY_ARRAY
        | self::STRING
        | self::BOOLEAN;

    private const TYPE_NAMES = [
        self::BOOLEAN       => 'boolean',
        self::INTEGER       => 'integer',
        self::FLOAT         => 'float',
        self::STRING        => 'string',
        self::ZERO          => 'zero',
        self::EMPTY_ARRAY   => 'array',
        self::NULL          => 'null',
        self::PHP           => 'php',
        self::SPACE         => 'space',
        self::OBJECT        => 'object',
        self::OBJECT_STRING => 'objectstring',
        self::OBJECT_COUNT  => 'objectcount',
        self::ALL           => 'all',
    ];

    /** @var array<string, string> */
    protected array $messageTemplates = [
        self::IS_EMPTY => "Value is required and can't be empty",
        self::INVALID  => 'Invalid type given. String, integer, float, boolean or array expected',
    ];

    /** @var TypeIntMask */
    private readonly int $type;

    /**
     * @param OptionsArgument $options
     */
    public function __construct(array $options = [])
    {
        $type       = $options['type'] ?? self::DEFAULT_TYPE;
        $this->type = $this->calculateTypeValue($type);

        unset($options['type']);

        parent::__construct($options);
    }

    /**
     * @param TypeArgument $type
     * @return TypeIntMask
     */
    private function calculateTypeValue(array|int|string $type): int
    {
        if (is_array($type)) {
            $detected = 0;
            foreach ($type as $value) {
                if (is_int($value) && ($value & self::ALL) !== 0) {
                    $detected |= $value;
                } elseif (in_array($value, self::TYPE_NAMES, true)) {
                    $detected |= (int) array_search($value, self::TYPE_NAMES, true);
                }
            }

            $type = $detected;
        } elseif (is_string($type) && in_array($type, self::TYPE_NAMES, true)) {
            $type = array_search($type, self::TYPE_NAMES, true);
        }

        assert(is_int($type) && ($type & self::ALL) !== 0);

        /** @psalm-var TypeIntMask $type */

        return $type;
    }

    /**
     * Returns true if and only if $value is not an empty value.
     */
    public function isValid(mixed $value): bool
    {
        if (
            $value !== null
            && ! is_string($value)
            && ! is_int($value)
            && ! is_float($value)
            && ! is_bool($value)
            && ! is_array($value)
            && ! is_object($value)
        ) {
            $this->error(self::INVALID);
            return false;
        }

        $this->setValue($value);
        $object = false;

        // OBJECT_COUNT (countable object)
        if ($this->type & self::OBJECT_COUNT) {
            $object = true;

            if ($value instanceof Countable && (count($value) === 0)) {
                $this->error(self::IS_EMPTY);
                return false;
            }
        }

        // OBJECT_STRING (object's toString)
        if ($this->type & self::OBJECT_STRING) {
            $object = true;

            if (
                (is_object($value) && ! method_exists($value, '__toString'))
                || (is_object($value) && (string) $value === '')
            ) {
                $this->error(self::IS_EMPTY);
                return false;
            }
        }

        // OBJECT (object)
        // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
        if ($this->type & self::OBJECT) {
            // fall through, objects are always not empty
        } elseif ($object === false) {
            // object not allowed but object given -> return false
            if (is_object($value)) {
                $this->error(self::IS_EMPTY);
                return false;
            }
        }

        // SPACE ('   ')
        if ($this->type & self::SPACE) {
            if (is_string($value) && (preg_match('/^\s+$/s', $value))) {
                $this->error(self::IS_EMPTY);
                return false;
            }
        }

        // NULL (null)
        if ($this->type & self::NULL) {
            if ($value === null) {
                $this->error(self::IS_EMPTY);
                return false;
            }
        }

        // EMPTY_ARRAY (array())
        if ($this->type & self::EMPTY_ARRAY) {
            if ($value === []) {
                $this->error(self::IS_EMPTY);
                return false;
            }
        }

        // ZERO ('0')
        if ($this->type & self::ZERO) {
            if ($value === '0') {
                $this->error(self::IS_EMPTY);
                return false;
            }
        }

        // STRING ('')
        if ($this->type & self::STRING) {
            if ($value === '') {
                $this->error(self::IS_EMPTY);
                return false;
            }
        }

        // FLOAT (0.0)
        if ($this->type & self::FLOAT) {
            if ($value === 0.0) {
                $this->error(self::IS_EMPTY);
                return false;
            }
        }

        // INTEGER (0)
        if ($this->type & self::INTEGER) {
            if ($value === 0) {
                $this->error(self::IS_EMPTY);
                return false;
            }
        }

        // BOOLEAN (false)
        if ($this->type & self::BOOLEAN) {
            if ($value === false) {
                $this->error(self::IS_EMPTY);
                return false;
            }
        }

        return true;
    }

    /**
     * Return the configured message templates
     *
     * This method is an affordance to laminas-inputfilter.
     * It needs to introspect configured message templates in order to provide a default error message for empty inputs.
     *
     * In future versions of laminas-validator, this method will likely be deprecated and removed. Please avoid.
     *
     * @internal
     *
     * @psalm-internal \Laminas
     * @return array<string, string>
     */
    public function getMessageTemplates(): array
    {
        return $this->messageTemplates;
    }
}
