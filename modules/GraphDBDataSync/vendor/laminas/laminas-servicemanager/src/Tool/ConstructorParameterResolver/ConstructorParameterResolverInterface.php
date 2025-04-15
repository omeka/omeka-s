<?php

declare(strict_types=1);

namespace Laminas\ServiceManager\Tool\ConstructorParameterResolver;

use Psr\Container\ContainerInterface;

interface ConstructorParameterResolverInterface
{
    /**
     * Returns already resolved values so that these can be directly passed into the constructor.
     *
     * @param class-string         $className
     * @param array<string,string> $aliases
     * @return list<mixed>
     */
    public function resolveConstructorParameters(
        string $className,
        ContainerInterface $container,
        array $aliases = [],
    ): array;

    /**
     * Returns service names and/or native fallback types which can be either used to retrieve services from container
     * or to be passed to the constructor directly.
     *
     * @param class-string         $className
     * @param array<string,string> $aliases
     * @return list<ServiceFromContainerConstructorParameter|FallbackConstructorParameter>
     */
    public function resolveConstructorParameterServiceNamesOrFallbackTypes(
        string $className,
        ContainerInterface $container,
        array $aliases = [],
    ): array;
}
