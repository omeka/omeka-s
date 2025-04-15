<?php

declare(strict_types=1);

namespace Laminas\ServiceManager\Tool\ConstructorParameterResolver;

use ArrayAccess;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;

use function array_map;
use function assert;
use function class_exists;
use function in_array;
use function interface_exists;
use function sprintf;

/**
 * @internal
 */
final class ConstructorParameterResolver implements ConstructorParameterResolverInterface
{
    /** {@inheritDoc} */
    public function resolveConstructorParameters(
        string $className,
        ContainerInterface $container,
        array $aliases = []
    ): array {
        $parameters = $this->resolveConstructorParameterServiceNamesOrFallbackTypes($className, $container, $aliases);

        return array_map(static function (
            FallbackConstructorParameter|ServiceFromContainerConstructorParameter $parameter
        ) use ($container): mixed {
            if ($parameter instanceof FallbackConstructorParameter) {
                return $parameter->argumentValue;
            }

            return $container->get($parameter->serviceName);
        }, $parameters);
    }

    /**
     * Resolve a parameter to a value.
     *
     * Returns a callback for resolving a parameter to a value, but without
     * allowing mapping array `$config` arguments to the `config` service.
     *
     * @param class-string $className
     * @param array<string,string> $aliases
     * @return callable(ReflectionParameter):(FallbackConstructorParameter|ServiceFromContainerConstructorParameter)
     */
    private function resolveParameterWithoutConfigService(
        ContainerInterface $container,
        string $className,
        array $aliases
    ): callable {
        return fn(ReflectionParameter $parameter): FallbackConstructorParameter|ServiceFromContainerConstructorParameter
        => $this->resolveParameter($parameter, $container, $className, $aliases);
    }

    /**
     * Returns a callback for resolving a parameter to a value, including mapping 'config' arguments.
     *
     * Unlike resolveParameter(), this version will detect `$config` array
     * arguments and have them return the 'config' service.
     *
     * @param class-string $className
     * @param array<string,string> $aliases
     * @return callable(ReflectionParameter):(FallbackConstructorParameter|ServiceFromContainerConstructorParameter)
     */
    private function resolveParameterWithConfigService(
        ContainerInterface $container,
        string $className,
        array $aliases
    ): callable {
        return function (
            ReflectionParameter $parameter
        ) use (
            $container,
            $className,
            $aliases
        ): FallbackConstructorParameter|ServiceFromContainerConstructorParameter {
            if ($parameter->getName() === 'config') {
                $type = $parameter->getType();
                if (
                    $type instanceof ReflectionNamedType
                    && in_array($type->getName(), ['array', ArrayAccess::class], true)
                ) {
                    return new ServiceFromContainerConstructorParameter('config');
                }
            }
            return $this->resolveParameter($parameter, $container, $className, $aliases);
        };
    }

    /**
     * Logic common to all parameter resolution.
     *
     * @param class-string $className
     * @param array<string,string> $aliases
     * @throws ServiceNotFoundException If type-hinted parameter cannot be
     *   resolved to a service in the container.
     */
    private function resolveParameter(
        ReflectionParameter $parameter,
        ContainerInterface $container,
        string $className,
        array $aliases
    ): FallbackConstructorParameter|ServiceFromContainerConstructorParameter {
        $type = $parameter->getType();
        $type = $type instanceof ReflectionNamedType ? $type->getName() : null;

        if ($type === null || (! class_exists($type) && ! interface_exists($type))) {
            if (! $parameter->isDefaultValueAvailable()) {
                throw new ServiceNotFoundException(sprintf(
                    'Unable to create service "%s"; unable to resolve parameter "%s" '
                    . 'to a class, interface, or array type',
                    $className,
                    $parameter->getName()
                ));
            }

            return new FallbackConstructorParameter($parameter->getDefaultValue());
        }

        $type = $aliases[$type] ?? $type;

        if ($container->has($type)) {
            assert($type !== '');
            return new ServiceFromContainerConstructorParameter($type);
        }

        if (! $parameter->isOptional()) {
            throw new ServiceNotFoundException(sprintf(
                'Unable to create service "%s"; unable to resolve parameter "%s" using type hint "%s"',
                $className,
                $parameter->getName(),
                $type
            ));
        }

        // Type not available in container, but the value is optional and has a
        // default defined.
        return new FallbackConstructorParameter($parameter->getDefaultValue());
    }

    /** {@inheritDoc} */
    public function resolveConstructorParameterServiceNamesOrFallbackTypes(
        string $className,
        ContainerInterface $container,
        array $aliases = [],
    ): array {
        $reflectionClass = new ReflectionClass($className);

        $constructor = $reflectionClass->getConstructor();
        if (null === $constructor) {
            return [];
        }

        $reflectionParameters = $constructor->getParameters();

        if ($reflectionParameters === []) {
            return [];
        }

        $resolver = $container->has('config')
            ? $this->resolveParameterWithConfigService($container, $className, $aliases)
            : $this->resolveParameterWithoutConfigService($container, $className, $aliases);

        return array_map($resolver, $reflectionParameters);
    }
}
