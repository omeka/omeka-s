<?php

declare(strict_types=1);

namespace Laminas\Validator;

use Laminas\Translator\TranslatorInterface;
use Laminas\Validator\Exception\InvalidArgumentException;

use function array_key_exists;
use function array_keys;
use function array_unique;
use function assert;
use function implode;
use function is_array;
use function is_bool;
use function is_object;
use function is_string;
use function key;
use function method_exists;
use function property_exists;
use function sprintf;
use function str_repeat;
use function str_replace;
use function strlen;
use function substr;
use function var_export;

use const SORT_REGULAR;

/**
 * @psalm-type AbstractOptions = array{
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 *     ...<string, mixed>
 * }
 */
abstract class AbstractValidator implements
    Translator\TranslatorAwareInterface,
    ValidatorInterface
{
    /**
     * The value to be validated
     *
     * phpcs:disable WebimpressCodingStandard.Classes.NoNullValues
     */
    protected mixed $value = null;

    /**
     * Default translation object for all validate objects
     */
    private static ?TranslatorInterface $defaultTranslator = null;

    /**
     * Default text domain to be used with translator
     */
    private static string $defaultTranslatorTextDomain = 'default';

    /**
     * Limits the maximum returned length of an error message
     */
    private static int $messageLength = -1;

    /**
     * An array that defines the default translations (in english) of the validators error messages
     *
     * @var array<string, string>
     */
    protected array $messageTemplates = [];

    /**
     * An array that defines substitutions that will be interpolated into error messages.
     *
     * Keys are the placeholder name and the values must be a string that references protected or public properties of
     * the validator, for example ['myProperty' => 'myProperty'] would replace "%myProperty%" in an error message with
     * the value of "$this->myProperty".
     *
     * You can also specify a replacement as an array such as ['myProperty' => ['props' => 'this-one']]. In this case,
     * the placeholder '%myProperty%' would be replaced with the value "$this->props['this-one']".
     *
     * @var array<string, string|array<string, string>>
     */
    protected array $messageVariables = [];

    /** Flag indicating whether value should be obfuscated in error messages */
    private bool $valueObscured = false;

    /** Whether translation should be enabled or not */
    private bool $translatorEnabled = true;

    /** The text domain for translations */
    private string $translatorTextDomain = 'default';

    /** A custom translator, the default translator, or null */
    private TranslatorInterface|null $translator = null;

    /**
     * Error messages that have occurred during the last validation
     *
     * @var array<string, string>
     */
    protected array $errorMessages = [];

    /**
     * Abstract constructor for all validators
     *
     * Custom validators should call `parent::__construct($options)` after processing validator specific options in
     * order to ensure that:
     * - User supplied, custom errors messages override the default error messages
     * - The configured translator is correctly set and/or enabled
     *
     * @param AbstractOptions $options
     */
    public function __construct(array $options = [])
    {
        $valueObscured        = $options['valueObscured'] ?? false;
        $translatorEnabled    = $options['translatorEnabled'] ?? true;
        $translatorTextDomain = $options['translatorTextDomain'] ?? self::$defaultTranslatorTextDomain;
        $translator           = $options['translator'] ?? self::$defaultTranslator;
        $messages             = $options['messages'] ?? [];

        assert(is_bool($translatorEnabled));
        assert(is_string($translatorTextDomain));
        assert($translator instanceof TranslatorInterface || $translator === null);
        assert(is_array($messages));

        $this->valueObscured        = $valueObscured;
        $this->translatorEnabled    = $translatorEnabled;
        $this->translatorTextDomain = $translatorTextDomain;
        $this->translator           = $translator;
        /** @psalm-var array<string, string> $messages Psalm cannot infer this from the declared type */
        $this->overrideMessagesWith($messages);
    }

    /**
     * Returns array of validation failure messages
     *
     * @return array<string, string>
     */
    public function getMessages(): array
    {
        return array_unique($this->errorMessages, SORT_REGULAR);
    }

    /**
     * Invoke as command
     */
    public function __invoke(mixed $value): bool
    {
        return $this->isValid($value);
    }

    /**
     * Sets the validation failure message template for a particular key
     *
     * Omitting the `$messageKey` parameter will cause _all_ error messages to have the same value.
     *
     * @throws InvalidArgumentException If the supplied $messageKey does not correspond to a known error message key.
     */
    public function setMessage(string $messageString, ?string $messageKey = null): void
    {
        if ($messageKey === null) {
            $keys = array_keys($this->messageTemplates);
            foreach ($keys as $key) {
                $this->setMessage($messageString, $key);
            }
            return;
        }

        if (! isset($this->messageTemplates[$messageKey])) {
            throw new InvalidArgumentException("No message template exists for key '$messageKey'");
        }

        $this->messageTemplates[$messageKey] = $messageString;
    }

    /**
     * Constructs and returns a validation failure message with the given message key and value.
     *
     * Returns null if and only if $messageKey does not correspond to an existing template.
     *
     * If a translator is available and a translation exists for $messageKey,
     * the translation will be used.
     */
    private function createMessage(string $messageKey, mixed $value): ?string
    {
        if (! isset($this->messageTemplates[$messageKey])) {
            return null;
        }

        $message = $this->translateMessage(
            $this->messageTemplates[$messageKey],
        );

        $message = $this->substitutePlaceholder(
            'value',
            $value,
            $message,
            $this->valueObscured,
        );

        foreach ($this->messageVariables as $id => $property) {
            $message = $this->substitutePlaceholder(
                $id,
                $this->propertyValue($property),
                $message,
                false,
            );
        }

        $length = self::$messageLength;
        if (($length > -1) && (strlen($message) > $length)) {
            $message = substr($message, 0, $length - 3) . '...';
        }

        return $message;
    }

    /** @param string|array<string, string> $prop */
    private function propertyValue(string|array $prop): mixed
    {
        if (is_string($prop)) {
            assert(property_exists($this, $prop));

            /** @psalm-var mixed $value */
            return $this->{$prop};
        }

        $name = key($prop);
        assert(is_string($name));
        assert(property_exists($this, $name));

        $key = $prop[$name];
        /** @psalm-var mixed $value */
        $value = $this->$name;
        assert(is_array($value));
        assert(array_key_exists($key, $value));

        return $value[$key];
    }

    private function substitutePlaceholder(string $id, mixed $value, string $message, bool $obscure): string
    {
        $search = "%$id%";
        $value  = $this->stringifyValue($value);
        if ($obscure) {
            $value = str_repeat('*', strlen($value));
        }

        return str_replace($search, $value, $message);
    }

    private function stringifyValue(mixed $value): string
    {
        if (is_object($value)) {
            return method_exists($value, '__toString')
                ? (string) $value
                : $value::class . ' object';
        }

        if (is_array($value)) {
            return var_export($value, true);
        }

        return (string) $value;
    }

    protected function error(string $messageKey, mixed $value = null): void
    {
        if ($value === null) {
            /** @psalm-var mixed $value */
            $value = $this->value;
        }

        $message = $this->createMessage($messageKey, $value);
        if (! is_string($message)) {
            return;
        }

        $this->errorMessages[$messageKey] = $message;
    }

    /**
     * Returns the validation value
     */
    protected function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Set the validated value
     *
     * Sets the validated value so that it can be interpolated in error messages and clears any previous validation
     * failure messages.
     */
    protected function setValue(mixed $value): void
    {
        $this->value         = $value;
        $this->errorMessages = [];
    }

    /**
     * Set the translator for this instance
     */
    public function setTranslator(?TranslatorInterface $translator = null, ?string $textDomain = null): void
    {
        $this->translator = $translator;
        if ($textDomain !== null) {
            $this->translatorTextDomain = $textDomain;
        }
    }

    /**
     * Return the translator for this instance
     */
    public function getTranslator(): ?TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * Set the default, static translator for all validators
     */
    public static function setDefaultTranslator(
        ?TranslatorInterface $translator = null,
        ?string $textDomain = null,
    ): void {
        self::$defaultTranslator = $translator;
        if (null !== $textDomain) {
            self::setDefaultTranslatorTextDomain($textDomain);
        }
    }

    /**
     * Set default translation text domain for all validator instances
     */
    public static function setDefaultTranslatorTextDomain(string $textDomain = 'default'): void
    {
        self::$defaultTranslatorTextDomain = $textDomain;
    }

    /**
     * Sets the maximum allowed message length for all validator instances
     */
    public static function setMessageLength(int $length = -1): void
    {
        self::$messageLength = $length;
    }

    /**
     * Translate a validation message
     */
    private function translateMessage(string $message): string
    {
        if (! $this->translatorEnabled || ! $this->translator) {
            return $message;
        }

        return $this->translator->translate($message, $this->translatorTextDomain);
    }

    /**
     * Overrides message templates for this instance
     *
     * @param array<string, string> $customMessages
     */
    private function overrideMessagesWith(array $customMessages): void
    {
        foreach ($customMessages as $key => $message) {
            if (! array_key_exists($key, $this->messageTemplates)) {
                throw new InvalidArgumentException(sprintf(
                    'The error message key "%s" does not exist. Possible keys are "%s"',
                    $key,
                    implode(', ', array_keys($this->messageTemplates)),
                ));
            }

            $this->messageTemplates[$key] = $message;
        }
    }
}
