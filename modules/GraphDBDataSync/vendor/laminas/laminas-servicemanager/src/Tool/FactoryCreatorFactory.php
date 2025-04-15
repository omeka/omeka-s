<?php

declare(strict_types=1);

namespace Laminas\ServiceManager\Tool;

use Laminas\ServiceManager\Tool\ConstructorParameterResolver\ConstructorParameterResolverInterface;
use Psr\Container\ContainerInterface;

use function assert;

/**
 * @internal
 */
final class FactoryCreatorFactory
{
    public function __invoke(ContainerInterface $container): FactoryCreatorInterface
    {
        $resolver = $container->get(ConstructorParameterResolverInterface::class);
        assert($resolver instanceof ConstructorParameterResolverInterface);
        return new FactoryCreator($container, $resolver);
    }
}
