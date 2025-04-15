<?php

declare(strict_types=1);

namespace Laminas\Validator;

use function assert;

/**
 * @psalm-import-type ValidatorSpecification from ValidatorInterface
 */
final class ValidatorChainFactory
{
    public function __construct(private readonly ValidatorPluginManager $pluginManager)
    {
    }

    /** @param array<array-key, ValidatorSpecification> $specification */
    public function fromArray(array $specification): ValidatorChain
    {
        $chain = new ValidatorChain($this->pluginManager);
        foreach ($specification as $spec) {
            $priority   = $spec['priority'] ?? ValidatorChain::DEFAULT_PRIORITY;
            $breakChain = $spec['break_chain_on_failure'] ?? false;
            $options    = $spec['options'] ?? [];
            $validator  = $this->pluginManager->build($spec['name'], $options);
            assert($validator instanceof ValidatorInterface);
            $chain->attach($validator, $breakChain, $priority);
        }

        return $chain;
    }
}
