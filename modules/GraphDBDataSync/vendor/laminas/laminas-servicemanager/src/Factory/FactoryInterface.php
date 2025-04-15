<?php

declare(strict_types=1);

namespace Laminas\ServiceManager\Factory;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * Interface for a factory
 *
 * A factory is an callable object that is able to create a service. It is
 * given the instance of the service locator, the requested name of the service
 * you want to create, and any additional options that could be used to
 * configure the service state.
 */
interface FactoryInterface
{
    /**
     * @throws ServiceNotFoundException If unable to resolve the service.
     * @throws ServiceNotCreatedException If an exception is raised when creating a service.
     * @throws ContainerExceptionInterface If any other error occurs.
     */
    public function __invoke(ContainerInterface $container, string $requestedName, ?array $options = null): mixed;
}
