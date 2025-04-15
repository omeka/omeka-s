<?php

declare(strict_types=1);

namespace Laminas\ServiceManager;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Interface for service locator
 */
interface ServiceLocatorInterface extends ContainerInterface
{
    /**
     * Builds a service by its name, using optional options (such services are NEVER cached).
     *
     * @template T of object
     * @param  string|class-string<T> $name
     * @psalm-return ($name is class-string<T> ? T : mixed)
     * @throws ServiceNotFoundException If no factory/abstract
     *     factory could be found to create the instance.
     * @throws ServiceNotCreatedException If factory/delegator fails
     *     to create the instance.
     * @throws ContainerExceptionInterface If any other error occurs.
     */
    public function build(string $name, ?array $options = null): mixed;

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @template T of object
     * @param string|class-string<T> $id
     * @psalm-return ($id is class-string<T> ? T : mixed)
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     * @throws NotFoundExceptionInterface No entry was found for **this** identifier.
     */
    public function get(string $id);
}
