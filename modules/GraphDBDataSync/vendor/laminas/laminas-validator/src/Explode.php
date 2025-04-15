<?php

declare(strict_types=1);

namespace Laminas\Validator;

use Laminas\ServiceManager\ServiceManager;
use Laminas\Translator\TranslatorInterface;
use Laminas\Validator\Exception\RuntimeException;

use function explode;
use function implode;
use function is_array;
use function is_string;
use function sprintf;

/**
 * @psalm-type OptionsArgument = array{
 *     valueDelimiter?: non-empty-string,
 *     validatorPluginManager?: ValidatorPluginManager|null,
 *     validator?: ValidatorInterface|class-string<ValidatorInterface>|ValidatorSpecification,
 *     breakOnFirstFailure?: bool,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 * @psalm-import-type ValidatorSpecification from ValidatorInterface
 */
final class Explode extends AbstractValidator
{
    public const INVALID      = 'explodeInvalid';
    public const INVALID_ITEM = 'invalidItem';

    /** @var array<string, string> */
    protected array $messageTemplates = [
        self::INVALID      => 'Invalid type given, string expected',
        self::INVALID_ITEM => '%count% items were invalid: %error%',
    ];

    /** @var non-empty-string */
    private readonly string $valueDelimiter;
    private ValidatorPluginManager|null $pluginManager;
    private readonly ValidatorInterface $validator;
    private readonly bool $breakOnFirstFailure;

    protected int $count     = 0;
    protected ?string $error = null;

    /** @var array<string, string|array<string, string>> */
    protected array $messageVariables = [
        'count' => 'count',
        'error' => 'error',
    ];

    /** @param OptionsArgument $options */
    public function __construct(array $options = [])
    {
        $this->valueDelimiter      = $options['valueDelimiter'] ?? ',';
        $plugins                   = $options['validatorPluginManager'] ?? null;
        $this->pluginManager       = $plugins instanceof ValidatorPluginManager ? $plugins : null;
        $this->validator           = $this->resolveValidator($options['validator'] ?? null);
        $this->breakOnFirstFailure = $options['breakOnFirstFailure'] ?? false;

        parent::__construct($options);
    }

    private function getPluginManager(): ValidatorPluginManager
    {
        if (! $this->pluginManager instanceof ValidatorPluginManager) {
            $this->pluginManager = new ValidatorPluginManager(new ServiceManager());
        }

        return $this->pluginManager;
    }

    /**
     * Sets the Validator for validating each value
     *
     * @param ValidatorInterface|string|ValidatorSpecification $validator
     * @throws RuntimeException
     */
    private function resolveValidator(ValidatorInterface|string|array|null $validator): ValidatorInterface
    {
        if ($validator instanceof ValidatorInterface) {
            return $validator;
        }

        if (is_array($validator)) {
            if (! isset($validator['name'])) {
                throw new RuntimeException(
                    'Invalid validator specification provided; does not include "name" key',
                );
            }
            $name    = $validator['name'];
            $options = $validator['options'] ?? [];

            return $this->getPluginManager()->build($name, $options);
        }

        if (is_string($validator)) {
            return $this->getPluginManager()->get($validator);
        }

        throw new RuntimeException(sprintf(
            '%s expects a validator to be set; none given',
            self::class,
        ));
    }

    /**
     * Defined by Laminas\Validator\ValidatorInterface
     *
     * Returns true if all values validate true
     *
     * @param array<string, mixed> $context
     * @throws RuntimeException
     */
    public function isValid(mixed $value, ?array $context = null): bool
    {
        if (! is_string($value)) {
            $this->error(self::INVALID);

            return false;
        }

        $this->setValue($value);

        $values      = explode($this->valueDelimiter, $value);
        $this->count = 0;

        foreach ($values as $value) {
            if ($this->validator->isValid($value, $context)) {
                continue;
            }

            $this->count++;

            if ($this->breakOnFirstFailure) {
                break;
            }
        }

        if ($this->count > 0) {
            $this->error = implode(', ', $this->validator->getMessages());

            $this->error(self::INVALID_ITEM);

            return false;
        }

        return true;
    }
}
