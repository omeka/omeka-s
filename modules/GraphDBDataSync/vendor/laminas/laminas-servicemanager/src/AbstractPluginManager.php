<?php

declare(strict_types=1);

namespace Laminas\ServiceManager;

use Laminas\ServiceManager\Exception\ContainerModificationsNotAllowedException;
use Laminas\ServiceManager\Exception\CyclicAliasException;
use Laminas\ServiceManager\Exception\InvalidServiceException;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use Laminas\ServiceManager\Factory\DelegatorFactoryInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\ServiceManager\Initializer\InitializerInterface;
use Laminas\Stdlib\ArrayUtils;
use Psr\Container\ContainerInterface;

use function class_exists;
use function sprintf;

/**
 * Abstract plugin manager.
 *
 * Abstract PluginManagerInterface implementation providing creation context support.
 * The constructor accepts the parent container instance, which is then used when creating instances.
 *
 * @template InstanceType
 * @template-implements PluginManagerInterface<InstanceType>
 * @psalm-import-type ServiceManagerConfiguration from ServiceManager
 * @psalm-import-type FactoryCallable from ServiceManager
 * @psalm-import-type DelegatorCallable from ServiceManager
 * @psalm-import-type InitializerCallable from ServiceManager
 * @psalm-import-type AbstractFactoriesConfiguration from ServiceManager
 * @psalm-import-type DelegatorsConfiguration from ServiceManager
 * @psalm-import-type FactoriesConfiguration from ServiceManager
 * @psalm-import-type InitializersConfiguration from ServiceManager
 * @psalm-import-type LazyServicesConfiguration from ServiceManager
 */
abstract class AbstractPluginManager implements PluginManagerInterface
{
    /**
     * Whether or not to auto-add a FQCN as an invokable if it exists.
     */
    protected bool $autoAddInvokableClass = true;

    protected bool $sharedByDefault = true;

    /**
     * @deprecated Please pass the plugin manager configuration via {@see AbstractPluginManager::__construct} instead.
     *
     * @var AbstractFactoryInterface[]
     */
    protected array $abstractFactories = [];

    /**
     * A list of aliases
     *
     * Should map one alias to a service name, or another alias (aliases are recursively resolved)
     *
     * @deprecated Please pass the plugin manager configuration via {@see AbstractPluginManager::__construct} instead.
     *
     * @var string[]
     */
    protected array $aliases = [];

    /**
     * @deprecated Please pass the plugin manager configuration via {@see AbstractPluginManager::__construct} instead.
     *
     * @var DelegatorsConfiguration
     */
    protected array $delegators = [];

    /**
     * A list of factories (either as string name or callable)
     *
     * @deprecated Please pass the plugin manager configuration via {@see AbstractPluginManager::__construct} instead.
     *
     * @var FactoriesConfiguration
     */
    protected array $factories = [];

    /**
     * @deprecated Please pass the plugin manager configuration via {@see AbstractPluginManager::__construct} instead.
     *
     * @var InitializersConfiguration
     */
    protected array $initializers = [];

    /**
     * @deprecated Please pass the plugin manager configuration via {@see AbstractPluginManager::__construct} instead.
     *
     * @var LazyServicesConfiguration
     */
    protected array $lazyServices = [];

    /**
     * A list of already loaded services (this act as a local cache)
     *
     * @deprecated Please pass the plugin manager configuration via {@see AbstractPluginManager::__construct} instead.
     *
     * @var array<string,mixed>
     */
    protected array $services = [];

    /**
     * Enable/disable shared instances by service name.
     *
     * Example configuration:
     *
     * 'shared' => [
     *     MyService::class => true, // will be shared, even if "sharedByDefault" is false
     *     MyOtherService::class => false // won't be shared, even if "sharedByDefault" is true
     * ]
     *
     * @deprecated Please pass the plugin manager configuration via {@see AbstractPluginManager::__construct} instead.
     *
     * @var array<string,bool>
     */
    protected array $shared = [];

    private readonly ServiceManager $plugins;

    /**
     * @param ServiceManagerConfiguration $config
     */
    public function __construct(
        ContainerInterface $creationContext,
        array $config = [],
    ) {
        $this->plugins = new ServiceManager([
            'shared_by_default' => $this->sharedByDefault,
        ], $creationContext);

        /** @var ServiceManagerConfiguration $config */
        $config = ArrayUtils::merge([
            'factories'          => $this->factories,
            'abstract_factories' => $this->abstractFactories,
            'aliases'            => $this->aliases,
            'services'           => $this->services,
            'lazy_services'      => $this->lazyServices,
            'shared'             => $this->shared,
            'delegators'         => $this->delegators,
            'initializers'       => $this->initializers,
        ], $config);

        $this->configure($config);
    }

    /**
     * @param ServiceManagerConfiguration $config
     * @throws ContainerModificationsNotAllowedException If the allow override flag has been toggled off, and a
     *                                                   service instanceexists for a given service.
     * @throws InvalidServiceException If an instance passed in the `services` configuration is invalid for the
     *                                 plugin manager.
     * @throws CyclicAliasException If the configuration contains aliases targeting themselves.
     */
    public function configure(array $config): static
    {
        if (isset($config['services'])) {
            foreach ($config['services'] as $service) {
                $this->validate($service);
            }
        }

        // phpcs:disable SlevomatCodingStandard.Commenting.InlineDocCommentDeclaration.MissingVariable
        /** @var ServiceManagerConfiguration $config */
        $this->plugins->configure($config);
        // phpcs:enable SlevomatCodingStandard.Commenting.InlineDocCommentDeclaration.MissingVariable

        return $this;
    }

    /**
     * @deprecated Please use {@see AbstractPluginManager::configure()} instead.
     *
     * @param string|class-string<InstanceType> $name
     * @param InstanceType $service
     */
    public function setService(string $name, mixed $service): void
    {
        $this->validate($service);
        $this->plugins->setService($name, $service);
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $id): mixed
    {
        if (! $this->has($id)) {
            if (! $this->autoAddInvokableClass || ! class_exists($id)) {
                throw new Exception\ServiceNotFoundException(sprintf(
                    'A plugin by the name "%s" was not found in the plugin manager %s',
                    $id,
                    static::class
                ));
            }

            $this->plugins->setFactory($id, Factory\InvokableFactory::class);
        }

        $instance = $this->plugins->get($id);
        $this->validate($instance);
        return $instance;
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $id): bool
    {
        return $this->plugins->has($id);
    }

    /**
     * {@inheritDoc}
     */
    public function build(string $name, ?array $options = null): mixed
    {
        $plugin = $this->plugins->build($name, $options);
        $this->validate($plugin);

        return $plugin;
    }

    /**
     * Add an alias.
     *
     * @deprecated Please use {@see AbstractPluginManager::configure()} instead.
     *
     * @throws ContainerModificationsNotAllowedException If $alias already
     *     exists as a service and overrides are disallowed.
     */
    public function setAlias(string $alias, string $target): void
    {
        $this->plugins->setAlias($alias, $target);
    }

    /**
     * Add an invokable class mapping.
     *
     * @deprecated Please use {@see AbstractPluginManager::configure()} instead.
     *
     * @param null|string $class Class to which to map; if omitted, $name is
     *     assumed.
     * @throws ContainerModificationsNotAllowedException If $name already
     *     exists as a service and overrides are disallowed.
     */
    public function setInvokableClass(string $name, string|null $class = null): void
    {
        $this->plugins->setInvokableClass($name, $class);
    }

    /**
     * Specify a factory for a given service name.
     *
     * @deprecated Please use {@see AbstractPluginManager::configure()} instead.
     *
     * @param class-string<FactoryInterface>|FactoryCallable|FactoryInterface $factory
     * @throws ContainerModificationsNotAllowedException If $name already
     *     exists as a service and overrides are disallowed.
     */
    public function setFactory(string $name, string|callable|Factory\FactoryInterface $factory): void
    {
        $this->plugins->setFactory($name, $factory);
    }

    /**
     * Create a lazy service mapping to a class.
     *
     * @deprecated Please use {@see AbstractPluginManager::configure()} instead.
     *
     * @param string|class-string $name Service name to map
     * @param null|class-string $class Class to which to map; if not provided, $name
     *     will be used for the mapping.
     */
    public function mapLazyService(string $name, string|null $class = null): void
    {
        $this->plugins->mapLazyService($name, $class);
    }

    /**
     * Add an abstract factory for resolving services.
     *
     * @deprecated Please use {@see AbstractPluginManager::configure()} instead.
     *
     * @param string|AbstractFactoryInterface $factory Abstract factory
     *     instance or class name.
     * @psalm-param class-string<AbstractFactoryInterface>|AbstractFactoryInterface $factory
     */
    public function addAbstractFactory(string|AbstractFactoryInterface $factory): void
    {
        $this->plugins->addAbstractFactory($factory);
    }

    /**
     * Add a delegator for a given service.
     *
     * @deprecated Please use {@see AbstractPluginManager::configure()} instead.
     *
     * @param string $name Service name
     * @param string|callable|DelegatorFactoryInterface $factory Delegator factory to assign.
     * @psalm-param class-string<DelegatorFactoryInterface>|DelegatorCallable $factory
     */
    public function addDelegator(string $name, string|callable|DelegatorFactoryInterface $factory): void
    {
        $this->plugins->addDelegator($name, $factory);
    }

    /**
     * Add an initializer.
     *
     * @deprecated Please use {@see AbstractPluginManager::configure()} instead.
     *
     * @psalm-param class-string<InitializerInterface>|InitializerCallable|InitializerInterface $initializer
     */
    public function addInitializer(string|callable|InitializerInterface $initializer): void
    {
        $this->plugins->addInitializer($initializer);
    }

    /**
     * Add a service sharing rule.
     *
     * @deprecated Please use {@see AbstractPluginManager::configure()} instead.
     *
     * @param bool $flag Whether or not the service should be shared.
     * @throws ContainerModificationsNotAllowedException If $name already
     *     exists as a service and overrides are disallowed.
     */
    public function setShared(string $name, bool $flag): void
    {
        $this->plugins->setShared($name, $flag);
    }

    public function getAllowOverride(): bool
    {
        return $this->plugins->getAllowOverride();
    }

    public function setAllowOverride(bool $flag): void
    {
        $this->plugins->setAllowOverride($flag);
    }
}
