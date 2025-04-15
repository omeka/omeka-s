<?php

declare(strict_types=1);

namespace Laminas\ServiceManager\AbstractFactory;

use Laminas\ServiceManager\Exception\InvalidArgumentException;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use Laminas\ServiceManager\Tool\ConstructorParameterResolver\ConstructorParameterResolver;
use Laminas\ServiceManager\Tool\ConstructorParameterResolver\ConstructorParameterResolverInterface;
use Psr\Container\ContainerInterface;
use ReflectionClass;

use function class_exists;
use function sprintf;

/**
 * Reflection-based factory.
 *
 * To ease development, this factory may be used for classes with
 * type-hinted arguments that resolve to services in the application
 * container; this allows omitting the step of writing a factory for
 * each controller.
 *
 * You may use it as either an abstract factory:
 *
 * <code>
 * 'service_manager' => [
 *     'abstract_factories' => [
 *         ReflectionBasedAbstractFactory::class,
 *     ],
 * ],
 * </code>
 *
 * Or as a factory, mapping a class name to it:
 *
 * <code>
 * 'service_manager' => [
 *     'factories' => [
 *         MyClassWithDependencies::class => ReflectionBasedAbstractFactory::class,
 *     ],
 * ],
 * </code>
 *
 * The latter approach is more explicit, and also more performant.
 *
 * The factory has the following constraints/features:
 *
 * - A parameter named `$config` typehinted as an array will receive the
 *   application "config" service (i.e., the merged configuration).
 * - Parameters type-hinted against array, but not named `$config` will
 *   be injected with an empty array.
 * - Scalar parameters will result in an exception being thrown, unless
 *   a default value is present; if the default is present, that will be used.
 * - If a service cannot be found for a given typehint, the factory will
 *   raise an exception detailing this.
 * - Some services provided by Laminas components do not have
 *   entries based on their class name (for historical reasons); the
 *   factory allows defining a map of these class/interface names to the
 *   corresponding service name to allow them to resolve.
 *
 * `$options` passed to the factory are ignored in all cases, as we cannot
 * make assumptions about which argument(s) they might replace.
 *
 * Based on the LazyControllerAbstractFactory from laminas-mvc.
 */
final class ReflectionBasedAbstractFactory implements AbstractFactoryInterface
{
    private readonly ConstructorParameterResolverInterface $constructorParameterResolver;

    /**
     * Allows overriding the internal list of aliases. These should be of the
     * form `class name => well-known service name`; see the documentation for
     * the `$aliases` property for details on what is accepted.
     *
     * @param array<string,string> $aliases
     */
    public function __construct(
        public readonly array $aliases = [],
        ?ConstructorParameterResolverInterface $constructorParameterResolver = null,
    ) {
        $this->constructorParameterResolver = $constructorParameterResolver ?? new ConstructorParameterResolver();
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(ContainerInterface $container, string $requestedName, ?array $options = null): object
    {
        if (! class_exists($requestedName)) {
            throw new InvalidArgumentException(sprintf('%s can only be used with class names.', self::class));
        }

        $parameters = $this->constructorParameterResolver->resolveConstructorParameters(
            $requestedName,
            $container,
            $this->aliases
        );

        return new $requestedName(...$parameters);
    }

    /** {@inheritDoc} */
    public function canCreate(ContainerInterface $container, string $requestedName): bool
    {
        return class_exists($requestedName) && $this->canCallConstructor($requestedName);
    }

    private function canCallConstructor(string $requestedName): bool
    {
        $constructor = (new ReflectionClass($requestedName))->getConstructor();

        return $constructor === null || $constructor->isPublic();
    }
}
