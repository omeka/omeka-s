<?php

declare(strict_types=1);

namespace Laminas\ServiceManager;

use Laminas\ServiceManager\Exception\InvalidServiceException;
use Psr\Container\ContainerExceptionInterface;

/**
 * Interface for a plugin manager
 *
 * A plugin manager is a specialized service locator used to create homogeneous objects
 *
 * @template InstanceType
 */
interface PluginManagerInterface extends ServiceLocatorInterface
{
    /**
     * Validate an instance
     *
     * @throws InvalidServiceException If created instance does not respect the
     *     constraint on type imposed by the plugin manager.
     * @throws ContainerExceptionInterface If any other error occurs.
     * @psalm-assert InstanceType $instance
     */
    public function validate(mixed $instance): void;

    /**
     * @template TRequestedInstance extends InstanceType
     * @psalm-param class-string<TRequestedInstance>|string $id Service name of plugin to retrieve.
     * @psalm-return ($id is class-string<TRequestedInstance> ? TRequestedInstance : InstanceType)
     * @throws Exception\ServiceNotFoundException If the manager does not have
     *     a service definition for the instance, and the service is not
     *     auto-invokable.
     * @throws InvalidServiceException If the plugin created is invalid for the
     *     plugin context.
     */
    public function get(string $id): mixed;

    /**
     * Build a service by its name, using optional options (such services are NEVER cached).
     *
     * @template TRequestedInstance extends InstanceType
     * @psalm-param string|class-string<TRequestedInstance> $name
     * @psalm-return ($name is class-string<TRequestedInstance> ? TRequestedInstance : InstanceType)
     * @throws Exception\ServiceNotFoundException If no factory/abstract
     *     factory could be found to create the instance.
     * @throws Exception\ServiceNotCreatedException If factory/delegator fails
     *     to create the instance.
     * @throws ContainerExceptionInterface If any other error occurs.
     */
    public function build(string $name, ?array $options = null): mixed;
}
