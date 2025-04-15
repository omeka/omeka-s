<?php

declare(strict_types=1);

namespace Laminas\Validator;

use Laminas\Translator\TranslatorInterface;
use Laminas\Validator\Exception\InvalidArgumentException;

use function class_exists;

/**
 * @psalm-type OptionsArgument = array{
 *     className: class-string,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
final class IsInstanceOf extends AbstractValidator
{
    public const NOT_INSTANCE_OF = 'notInstanceOf';

    /**
     * Validation failure message template definitions
     *
     * @var array<string, string>
     */
    protected array $messageTemplates = [
        self::NOT_INSTANCE_OF => "The input is not an instance of '%className%'",
    ];

    /** @var array<string, string|array<string, string>> */
    protected array $messageVariables = [
        'className' => 'className',
    ];

    /** @var class-string */
    protected readonly string $className;

    /**
     * Sets validator options
     *
     * @param OptionsArgument $options
     * @throws InvalidArgumentException
     */
    public function __construct(array $options)
    {
        $className = $options['className'] ?? null;

        if ($className === null || ! class_exists($className)) {
            throw new InvalidArgumentException(
                'The className option must be a non-empty class-string for an existing class',
            );
        }

        $this->className = $className;

        unset($options['className']);

        parent::__construct($options);
    }

    /**
     * Returns true if $value is instance of $this->className
     */
    public function isValid(mixed $value): bool
    {
        if ($value instanceof $this->className) {
            return true;
        }

        $this->error(self::NOT_INSTANCE_OF);

        return false;
    }
}
