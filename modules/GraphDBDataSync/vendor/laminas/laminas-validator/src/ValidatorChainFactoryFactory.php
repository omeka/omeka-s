<?php

declare(strict_types=1);

namespace Laminas\Validator;

use Psr\Container\ContainerInterface;

final class ValidatorChainFactoryFactory
{
    public function __invoke(ContainerInterface $container): ValidatorChainFactory
    {
        return new ValidatorChainFactory($container->get(ValidatorPluginManager::class));
    }
}
