<?php

declare(strict_types=1);

namespace Laminas\Validator;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

final class ConditionalFactory implements FactoryInterface
{
    /** @inheritDoc */
    public function __invoke(
        ContainerInterface $container,
        string $requestedName,
        ?array $options = null,
    ): Conditional {
        /** @psalm-suppress MixedArgumentTypeCoercion - It's not worth attempting runtime validation of the options shape here */
        return new Conditional(
            $container->get(ValidatorChainFactory::class),
            $options ?? [],
        );
    }
}
