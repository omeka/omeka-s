<?php

declare(strict_types=1);

namespace Laminas\Validator;

use Closure;
use Laminas\Validator\Exception\InvalidArgumentException;

use function is_callable;

/**
 * @psalm-import-type ValidatorSpecification from ValidatorInterface
 * @psalm-type OptionsArgument = array{
 *     rule: callable(array<string, mixed>): bool,
 *     validators: array<array-key, ValidatorSpecification>,
 * }
 */
final class Conditional implements ValidatorInterface
{
    /** @var Closure(array<string, mixed>): bool */
    private readonly Closure $rule;
    private readonly ValidatorChain $chain;

    /** @param OptionsArgument $options */
    public function __construct(ValidatorChainFactory $chainFactory, array $options)
    {
        $rule = $options['rule'] ?? null;
        if (! is_callable($rule)) {
            throw new InvalidArgumentException('The `rule` option must be callable');
        }

        $this->rule  = ($rule)(...);
        $this->chain = $chainFactory->fromArray($options['validators']);
    }

    /** @param array<string, mixed> $context */
    public function isValid(mixed $value, array $context = []): bool
    {
        if (! ($this->rule)($context)) {
            return true;
        }

        return $this->chain->isValid($value, $context);
    }

    /** @inheritDoc */
    public function getMessages(): array
    {
        return $this->chain->getMessages();
    }
}
