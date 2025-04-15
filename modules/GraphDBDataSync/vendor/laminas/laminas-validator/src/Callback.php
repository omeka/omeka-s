<?php

declare(strict_types=1);

namespace Laminas\Validator;

use Closure;
use Exception;
use Laminas\Translator\TranslatorInterface;
use Laminas\Validator\Exception\InvalidArgumentException;

use function assert;
use function is_array;
use function is_bool;
use function is_callable;

/**
 * @psalm-type OptionsArgument = array{
 *     callback: callable(mixed, array<string, mixed>, mixed...): bool,
 *     callbackOptions?: array<array-key, mixed>,
 *     bind?: bool,
 *     throwExceptions?: bool,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
final class Callback extends AbstractValidator
{
    public const INVALID_CALLBACK = 'callbackInvalid';
    public const INVALID_VALUE    = 'callbackValue';

    /**
     * Validation failure message template definitions
     *
     * @var array<string, string>
     */
    protected array $messageTemplates = [
        self::INVALID_VALUE    => 'The input is not valid',
        self::INVALID_CALLBACK => 'An exception has been raised within the callback',
    ];

    /** @var Closure(mixed, array<string, mixed>, mixed...): bool */
    private readonly Closure $callback;
    private readonly bool $throwExceptions;
    /** @var array<array-key, mixed> */
    private readonly array $callbackOptions;

    /** @param OptionsArgument|callable $options */
    public function __construct(array|callable $options)
    {
        if (! is_array($options)) {
            $options = ['callback' => $options];
        }

        /** @psalm-var OptionsArgument&array<string, mixed> $options */

        $callback        = $options['callback'] ?? null;
        $callbackOptions = $options['callbackOptions'] ?? [];
        $throw           = $options['throwExceptions'] ?? false;
        $bind            = $options['bind'] ?? false;

        unset($options['callback'], $options['callbackOptions'], $options['throwExceptions'], $options['bind']);

        if (! is_callable($callback)) {
            throw new InvalidArgumentException('A callable must be provided');
        }

        assert(is_bool($throw));
        assert(is_bool($bind));

        /** @psalm-var Closure(mixed...):bool $callback */
        $callback       = $bind
            ? $callback(...)->bindTo($this)
            : $callback(...);
        $this->callback = $callback;

        $this->throwExceptions = $throw;
        $this->callbackOptions = $callbackOptions;

        parent::__construct($options);
    }

    /**
     * Returns true if and only if the set callback returns true for the provided $value
     *
     * @param array<string, mixed> $context Additional context to provide to the callback
     */
    public function isValid(mixed $value, ?array $context = null): bool
    {
        $this->setValue($value);

        try {
            $result = ($this->callback)($value, $context ?? [], ...$this->callbackOptions);
        } catch (Exception $exception) {
            /**
             * Intentionally excluding catchable \Error as they are indicative of a bug and should not be suppressed
             */
            $this->error(self::INVALID_CALLBACK);

            if ($this->throwExceptions) {
                throw $exception;
            }

            return false;
        }

        if ($result !== true) {
            $this->error(self::INVALID_VALUE);

            return false;
        }

        return true;
    }
}
