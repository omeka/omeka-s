<?php

declare(strict_types=1);

namespace Laminas\ServiceManager;

use Exception;
use Laminas\ServiceManager\Exception\ContainerModificationsNotAllowedException;
use Laminas\ServiceManager\Exception\CyclicAliasException;
use Laminas\ServiceManager\Exception\InvalidArgumentException;
use Laminas\ServiceManager\Exception\InvalidServiceException;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use Laminas\ServiceManager\Factory\DelegatorFactoryInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\ServiceManager\Initializer\InitializerInterface;
use Laminas\ServiceManager\Proxy\LazyServiceFactory;
use Laminas\Stdlib\ArrayUtils;
use ProxyManager\Configuration as ProxyConfiguration;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\FileLocator\FileLocator;
use ProxyManager\GeneratorStrategy\EvaluatingGeneratorStrategy;
use ProxyManager\GeneratorStrategy\FileWriterGeneratorStrategy;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

use function array_intersect;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function class_exists;
use function in_array;
use function is_callable;
use function is_string;
use function spl_autoload_register;
use function spl_object_hash;
use function sprintf;

/**
 * Service Manager.
 *
 * Default implementation of the ServiceLocatorInterface, providing capabilities
 * for object creation via:
 *
 * - factories
 * - abstract factories
 * - delegator factories
 * - lazy service factories (generated proxies)
 * - initializers (interface injection)
 *
 * It also provides the ability to inject specific service instances and to
 * define aliases.
 *
 * @see ContainerInterface
 * @see DelegatorFactoryInterface
 * @see AbstractFactoryInterface
 * @see FactoryInterface
 *
 * @psalm-type AbstractFactoriesConfiguration = array<
 *      array-key,
 *      class-string<AbstractFactoryInterface>|AbstractFactoryInterface
 * >
 * @psalm-type DelegatorCallable = callable(ContainerInterface,string,callable():mixed,array<mixed>|null):mixed
 * @psalm-type DelegatorsConfiguration = array<
 *      string,
 *      array<
 *          array-key,
 *          class-string<DelegatorFactoryInterface>
 *          |class-string<object&DelegatorCallable>
 *          |DelegatorFactoryInterface
 *          |DelegatorCallable
 *      >
 * >
 * @psalm-type FactoryCallable = callable(ContainerInterface,string,array<mixed>|null):mixed
 * @psalm-type FactoriesConfiguration = array<
 *      string,
 *      class-string<FactoryInterface>|class-string<object&FactoryCallable>|FactoryInterface|FactoryCallable
 * >
 * @psalm-type InitializerCallable = callable(ContainerInterface,mixed):void
 * @psalm-type InitializersConfiguration = array<
 *      array-key,
 *      class-string<InitializerInterface>|class-string<object&InitializerCallable>|InitializerInterface|InitializerCallable
 * >
 * @psalm-type LazyServicesConfiguration = array{
 *      class_map?:array<string,class-string>,
 *      proxies_namespace?:non-empty-string,
 *      proxies_target_dir?:non-empty-string,
 *      write_proxy_files?:bool
 * }
 * @psalm-type ServiceManagerConfiguration = array{
 *     abstract_factories?: AbstractFactoriesConfiguration,
 *     aliases?: array<string,string>,
 *     delegators?: DelegatorsConfiguration,
 *     factories?: FactoriesConfiguration,
 *     initializers?: InitializersConfiguration,
 *     invokables?: array<string,class-string>,
 *     lazy_services?: LazyServicesConfiguration,
 *     services?: array<string,mixed>,
 *     shared?:array<string,bool>,
 *     shared_by_default?: bool,
 *     ...
 * }
 *
 * @final Will be marked as final with v5.0.0
 */
class ServiceManager implements ServiceLocatorInterface
{
    /** @var AbstractFactoryInterface[] */
    protected array $abstractFactories = [];

    /**
     * A list of aliases
     *
     * Should map one alias to a service name, or another alias (aliases are recursively resolved)
     *
     * @var array<string,string>
     */
    protected array $aliases = [];

    /**
     * Whether or not changes may be made to this instance.
     */
    protected bool $allowOverride = false;

    protected ContainerInterface $creationContext;

    /** @var DelegatorsConfiguration */
    protected array $delegators = [];

    /**
     * A list of factories (either as string name or callable)
     *
     * @var FactoriesConfiguration
     */
    protected array $factories = [];

    /** @var list<InitializerInterface|InitializerCallable> */
    protected array $initializers = [];

    /** @var LazyServicesConfiguration */
    protected array $lazyServices = [];

    private ?LazyServiceFactory $lazyServicesDelegator = null;

    /**
     * A list of already loaded services (this act as a local cache)
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
     * @var array<string,bool>
     */
    protected array $shared = [];

    /**
     * Should the services be shared by default?
     */
    protected bool $sharedByDefault = true;

    /**
     * Service manager was already configured?
     */
    protected bool $configured = false;

    /**
     * Cached abstract factories from string.
     *
     * @var array<class-string<AbstractFactoryInterface>,AbstractFactoryInterface>
     */
    private array $cachedAbstractFactories = [];

    /**
     * See {@see \Laminas\ServiceManager\ServiceManager::configure()} for details
     * on what $config accepts.
     *
     * @param ServiceManagerConfiguration $config
     */
    public function __construct(
        array $config = [],
        ContainerInterface|null $creationContext = null
    ) {
        $this->creationContext = $creationContext ?? $this;
        $this->configure($config);
    }

    /** {@inheritDoc} */
    public function get(string $id): mixed
    {
        // We start by checking if we have cached the requested service;
        // this is the fastest method.
        if (isset($this->services[$id])) {
            /** @psalm-suppress MixedReturnStatement Yes indeed, service managers can return mixed. */
            return $this->services[$id];
        }

        // Determine if the service should be shared.
        $sharedService = $this->shared[$id] ?? $this->sharedByDefault;

        // We achieve better performance if we can let all alias
        // considerations out.
        if (! $this->aliases) {
            $service = $this->doCreate($id);

            // Cache the service for later, if it is supposed to be shared.
            if ($sharedService) {
                $this->services[$id] = $service;
            }

            /** @psalm-suppress MixedReturnStatement Yes indeed, service managers can return mixed. */
            return $service;
        }

        // We now deal with requests which may be aliases.
        $resolvedName = $this->aliases[$id] ?? $id;

        // Update shared service information as we checked if the alias was shared before.
        if ($resolvedName !== $id) {
            $sharedService = $this->shared[$resolvedName] ?? $sharedService;
        }

        // The following is only true if the requested service is a shared alias.
        $sharedAlias = $sharedService && isset($this->services[$resolvedName]);

        // If the alias is configured as a shared service, we are done.
        if ($sharedAlias) {
            $this->services[$id] = $this->services[$resolvedName];

            /** @psalm-suppress MixedReturnStatement Yes indeed, service managers can return mixed. */
            return $this->services[$resolvedName];
        }

        // At this point, we have to create the object.
        // We use the resolved name for that.
        $service = $this->doCreate($resolvedName);

        // Cache the object for later, if it is supposed to be shared.
        if ($sharedService) {
            $this->services[$resolvedName] = $service;
            $this->services[$id]           = $service;
        }

        /** @psalm-suppress MixedReturnStatement Yes indeed, service managers can return mixed. */
        return $service;
    }

    /** {@inheritDoc} */
    public function build(string $name, ?array $options = null): mixed
    {
        // We never cache when using "build".
        $name = $this->aliases[$name] ?? $name;
        /** @psalm-suppress MixedReturnStatement Yes indeed, service managers can return mixed. */
        return $this->doCreate($name, $options);
    }

    /**
     * {@inheritDoc}
     *
     * @param string|class-string $id
     * @return bool
     */
    public function has(string $id): bool
    {
        // Check static services and factories first to speedup the most common requests.
        return $this->staticServiceOrFactoryCanCreate($id) || $this->abstractFactoryCanCreate($id);
    }

    /**
     * Indicate whether or not the instance is immutable.
     */
    public function setAllowOverride(bool $flag): void
    {
        $this->allowOverride = $flag;
    }

    /**
     * Retrieve the flag indicating immutability status.
     */
    public function getAllowOverride(): bool
    {
        return $this->allowOverride;
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
        // This is a bulk update/initial configuration,
        // so we check all definitions up front.
        $this->validateServiceNames($config);

        if (isset($config['services'])) {
            $this->services = $config['services'] + $this->services;
        }

        if (isset($config['invokables']) && $config['invokables'] !== []) {
            $newAliases = $this->createAliasesAndFactoriesForInvokables($config['invokables']);
            // override existing aliases with those created by invokables to ensure
            // that they are still present after merging aliases later on
            $config['aliases'] = $newAliases + ($config['aliases'] ?? []);
        }

        if (isset($config['factories'])) {
            $this->factories = $config['factories'] + $this->factories;
        }

        if (isset($config['delegators'])) {
            $this->mergeDelegators($config['delegators']);
        }

        if (isset($config['shared'])) {
            $this->shared = $config['shared'] + $this->shared;
        }

        if (isset($config['aliases']) && $config['aliases'] !== []) {
            $this->aliases = $config['aliases'] + $this->aliases;
            $this->mapAliasesToTargets();
        } elseif (! $this->configured && $this->aliases !== []) {
            $this->mapAliasesToTargets();
        }

        if (isset($config['shared_by_default'])) {
            $this->sharedByDefault = $config['shared_by_default'];
        }

        // If lazy service configuration was provided, reset the lazy services
        // delegator factory.
        if (isset($config['lazy_services']) && $config['lazy_services'] !== []) {
            /** @psalm-suppress MixedPropertyTypeCoercion */
            $this->lazyServices          = ArrayUtils::merge($this->lazyServices, $config['lazy_services']);
            $this->lazyServicesDelegator = null;
        }

        // For abstract factories and initializers, we always directly
        // instantiate them to avoid checks during service construction.
        if (isset($config['abstract_factories'])) {
            $abstractFactories = $config['abstract_factories'];
            // $key not needed, but foreach is faster than foreach + array_values.
            foreach ($abstractFactories as $abstractFactory) {
                $this->resolveAbstractFactoryInstance($abstractFactory);
            }
        }

        if (isset($config['initializers'])) {
            $this->resolveInitializers($config['initializers']);
        }

        $this->configured = true;

        return $this;
    }

    /**
     * Add an alias.
     *
     * @throws ContainerModificationsNotAllowedException If $alias already
     *     exists as a service and overrides are disallowed.
     */
    public function setAlias(string $alias, string $target): void
    {
        if (isset($this->services[$alias]) && ! $this->allowOverride) {
            throw ContainerModificationsNotAllowedException::fromExistingService($alias);
        }

        $this->mapAliasToTarget($alias, $target);
    }

    /**
     * Add an invokable class mapping.
     *
     * @param string $name Service name
     * @param null|string $class Class to which to map; if omitted, $name is
     *     assumed.
     * @throws ContainerModificationsNotAllowedException If $name already
     *     exists as a service and overrides are disallowed.
     */
    public function setInvokableClass(string $name, ?string $class = null): void
    {
        if (isset($this->services[$name]) && ! $this->allowOverride) {
            throw ContainerModificationsNotAllowedException::fromExistingService($name);
        }

        $this->createAliasesAndFactoriesForInvokables([$name => $class ?? $name]);
    }

    /**
     * Specify a factory for a given service name.
     *
     * @param string $name Service name
     * @param string|callable|FactoryInterface $factory  Factory to which to map.
     * @phpcs:disable Generic.Files.LineLength.TooLong
     * @psalm-param class-string<FactoryInterface>|class-string<object&FactoryCallable>|FactoryCallable|FactoryInterface $factory
     * @phpcs:enable Generic.Files.LineLength.TooLong
     * @throws ContainerModificationsNotAllowedException If $name already
     *     exists as a service and overrides are disallowed.
     */
    public function setFactory(string $name, string|callable|FactoryInterface $factory): void
    {
        if (isset($this->services[$name]) && ! $this->allowOverride) {
            throw ContainerModificationsNotAllowedException::fromExistingService($name);
        }

        $this->factories[$name] = $factory;
    }

    /**
     * Create a lazy service mapping to a class.
     *
     * @param string|class-string $name Service name to map
     * @param null|class-string $class Class to which to map; if not provided, $name
     *     will be used for the mapping.
     */
    public function mapLazyService(string $name, ?string $class = null): void
    {
        $targetClassName = $class ?? $name;
        if (! class_exists($targetClassName)) {
            $message = sprintf('Provided service name "%s" must be a `class-string`.', $name);
            if ($class !== null) {
                $message = sprintf(
                    'Provided service name "%s" must target to a `class-string`. "%s" provided.',
                    $name,
                    $class,
                );
            }

            throw new InvalidArgumentException($message);
        }

        $this->configure(['lazy_services' => ['class_map' => [$name => $targetClassName]]]);
    }

    /**
     * Add an abstract factory for resolving services.
     *
     * @param string|AbstractFactoryInterface $factory Abstract factory
     *     instance or class name.
     * @psalm-param class-string<AbstractFactoryInterface>|AbstractFactoryInterface $factory
     */
    public function addAbstractFactory(string|AbstractFactoryInterface $factory): void
    {
        $this->resolveAbstractFactoryInstance($factory);
    }

    /**
     * Add a delegator for a given service.
     *
     * @param string $name Service name
     * @param string|callable|DelegatorFactoryInterface $factory Delegator
     *     factory to assign.
     * @phpcs:disable Generic.Files.LineLength.TooLong
     * @psalm-param class-string<DelegatorFactoryInterface>|class-string<object&DelegatorCallable>|DelegatorCallable|DelegatorFactoryInterface $factory
     * @phpcs:enable Generic.Files.LineLength.TooLong
     */
    public function addDelegator(string $name, string|callable|DelegatorFactoryInterface $factory): void
    {
        $this->configure(['delegators' => [$name => [$factory]]]);
    }

    /**
     * Add an initializer.
     *
     * @phpcs:disable Generic.Files.LineLength.TooLong
     * @psalm-param class-string<InitializerInterface>|class-string<object&InitializerCallable>|InitializerCallable|InitializerInterface $initializer
     * @phpcs:enable Generic.Files.LineLength.TooLong
     */
    public function addInitializer(string|callable|InitializerInterface $initializer): void
    {
        $this->configure(['initializers' => [$initializer]]);
    }

    /**
     * Map a service.
     *
     * @param string $name Service name
     * @throws ContainerModificationsNotAllowedException If $name already
     *     exists as a service and overrides are disallowed.
     */
    public function setService(string $name, mixed $service): void
    {
        if (isset($this->services[$name]) && ! $this->allowOverride) {
            throw ContainerModificationsNotAllowedException::fromExistingService($name);
        }
        $this->services[$name] = $service;
    }

    /**
     * Add a service sharing rule.
     *
     * @param string $name Service name
     * @param bool $flag Whether or not the service should be shared.
     * @throws ContainerModificationsNotAllowedException If $name already
     *     exists as a service and overrides are disallowed.
     */
    public function setShared(string $name, bool $flag): void
    {
        if (isset($this->services[$name]) && ! $this->allowOverride) {
            throw ContainerModificationsNotAllowedException::fromExistingService($name);
        }

        $this->shared[$name] = $flag;
    }

    /**
     * Instantiate initializers for to avoid checks during service construction.
     *
     * @param InitializersConfiguration $initializers
     */
    private function resolveInitializers(array $initializers): void
    {
        $resolved = [];
        foreach ($initializers as $initializer) {
            if (is_string($initializer) && class_exists($initializer)) {
                /**
                 * @psalm-suppress MixedMethodCall We are calling an unknown initializer.
                 *                                 We have to trust that the class is instantiable.
                 */
                $initializer = new $initializer();
            }

            if (is_callable($initializer)) {
                /** @psalm-var InitializerCallable $initializer */
                $resolved[] = $initializer;
                continue;
            }

            throw InvalidArgumentException::fromInvalidInitializer($initializer);
        }

        $this->initializers = array_merge($this->initializers, $resolved);
    }

    /**
     * Get a factory for the given service name
     *
     * @return FactoryCallable|FactoryInterface
     * @throws ServiceNotFoundException In case that the service creation strategy based on factories
     *                                  did not find any capable factory.
     */
    private function getFactory(string $name): callable|FactoryInterface
    {
        $factory = $this->factories[$name] ?? null;

        $lazyLoaded = false;
        if (is_string($factory) && class_exists($factory)) {
            /** @psalm-suppress MixedMethodCall We have to trust that the factory is instantiable. */
            $factory    = new $factory();
            $lazyLoaded = true;
        }

        if (is_callable($factory)) {
            /** @psalm-var FactoryCallable $factory */
            if ($lazyLoaded) {
                $this->factories[$name] = $factory;
            }

            return $factory;
        }

        // Check abstract factories
        foreach ($this->abstractFactories as $abstractFactory) {
            if ($abstractFactory->canCreate($this->creationContext, $name)) {
                return $abstractFactory;
            }
        }

        throw new ServiceNotFoundException(sprintf(
            'Unable to resolve service "%s" to a factory; are you certain you provided it during configuration?',
            $name
        ));
    }

    private function createDelegatorFromName(string $name, ?array $options = null): mixed
    {
        $creationCallback = function () use ($name, $options) {
            // Code is inlined for performance reason, instead of abstracting the creation
            $factory = $this->getFactory($name);
            return $factory($this->creationContext, $name, $options);
        };

        $initialCreationContext = $this->creationContext;

        $resolvedDelegators = [];
        foreach ($this->delegators[$name] as $index => $delegatorFactory) {
            /** @psalm-suppress ArgumentTypeCoercion https://github.com/vimeo/psalm/issues/9680 */
            $delegatorFactory                = $this->resolveDelegatorFactory($delegatorFactory);
            $this->delegators[$name][$index] = $delegatorFactory;
            $creationCallback                =
                static fn(): mixed => $delegatorFactory($initialCreationContext, $name, $creationCallback, $options);
        }

        $this->delegators[$name] = $resolvedDelegators;

        return $creationCallback();
    }

    /**
     * Create a new instance with an already resolved name
     *
     * This is a highly performance sensitive method, do not modify if you have not benchmarked it carefully
     *
     * @throws ServiceNotFoundException If unable to resolve the service.
     * @throws ServiceNotCreatedException If an exception is raised when creating a service.
     * @throws ContainerExceptionInterface If any other error occurs.
     */
    private function doCreate(string $resolvedName, ?array $options = null): mixed
    {
        try {
            if (! isset($this->delegators[$resolvedName])) {
                // Let's create the service by fetching the factory
                $factory = $this->getFactory($resolvedName);
                $service = $factory($this->creationContext, $resolvedName, $options);
            } else {
                $service = $this->createDelegatorFromName($resolvedName, $options);
            }
        } catch (ContainerExceptionInterface $exception) {
            throw $exception;
        } catch (Exception $exception) {
            throw new ServiceNotCreatedException(sprintf(
                'Service with name "%s" could not be created. Reason: %s',
                $resolvedName,
                $exception->getMessage()
            ), (int) $exception->getCode(), $exception);
        }

        foreach ($this->initializers as $initializer) {
            $initializer($this->creationContext, $service);
        }

        return $service;
    }

    /**
     * Create the lazy services delegator factory.
     *
     * Creates the lazy services delegator factory based on the lazy_services
     * configuration present.
     *
     * @throws ServiceNotCreatedException When the lazy service class_map configuration is missing.
     */
    private function createLazyServiceDelegatorFactory(): LazyServiceFactory
    {
        if ($this->lazyServicesDelegator) {
            return $this->lazyServicesDelegator;
        }

        if (! isset($this->lazyServices['class_map'])) {
            throw new ServiceNotCreatedException('Missing "class_map" config key in "lazy_services"');
        }

        $factoryConfig = new ProxyConfiguration();

        if (isset($this->lazyServices['proxies_namespace'])) {
            $factoryConfig->setProxiesNamespace($this->lazyServices['proxies_namespace']);
        }

        if (isset($this->lazyServices['proxies_target_dir'])) {
            $factoryConfig->setProxiesTargetDir($this->lazyServices['proxies_target_dir']);
        }

        if (! isset($this->lazyServices['write_proxy_files']) || ! $this->lazyServices['write_proxy_files']) {
            $factoryConfig->setGeneratorStrategy(new EvaluatingGeneratorStrategy());
        } else {
            $factoryConfig->setGeneratorStrategy(new FileWriterGeneratorStrategy(
                new FileLocator($factoryConfig->getProxiesTargetDir())
            ));
        }

        spl_autoload_register($factoryConfig->getProxyAutoloader());

        $this->lazyServicesDelegator = new LazyServiceFactory(
            new LazyLoadingValueHolderFactory($factoryConfig),
            $this->lazyServices['class_map']
        );

        return $this->lazyServicesDelegator;
    }

    /**
     * Merge delegators avoiding multiple same delegators for the same service.
     * It works with strings and class instances.
     * It's not possible to de-duple anonymous functions
     *
     * @param DelegatorsConfiguration $config
     * @return DelegatorsConfiguration
     */
    private function mergeDelegators(array $config): array
    {
        foreach ($config as $key => $delegators) {
            if (! array_key_exists($key, $this->delegators)) {
                $this->delegators[$key] = [];
            }

            foreach ($delegators as $delegator) {
                if (! in_array($delegator, $this->delegators[$key], true)) {
                    $this->delegators[$key][] = $delegator;
                }
            }
        }

        return $this->delegators;
    }

    /**
     * Create aliases and factories for invokable classes.
     *
     * If an invokable service name does not match the class it maps to, this
     * creates an alias to the class (which will later be mapped as an
     * invokable factory). The newly created aliases will be returned as an array.
     *
     * @param array<string,string> $invokables
     * @return array<string,string>
     */
    private function createAliasesAndFactoriesForInvokables(array $invokables): array
    {
        $newAliases = [];

        foreach ($invokables as $name => $class) {
            $this->factories[$class] = InvokableFactory::class;
            if ($name !== $class) {
                $this->aliases[$name] = $class;
                $newAliases[$name]    = $class;
            }
        }

        return $newAliases;
    }

    /**
     * Determine if a service for any name provided by a service
     * manager configuration(services, aliases, factories, ...)
     * already exists, and if it exists, determine if is it allowed
     * to get overriden.
     *
     * Validation in the context of this class means, that for
     * a given service name we do not have a service instance
     * in the cache OR override is explicitly allowed.
     *
     * @param ServiceManagerConfiguration $config
     * @throws ContainerModificationsNotAllowedException If any
     *     service key is invalid.
     */
    private function validateServiceNames(array $config): void
    {
        if ($this->allowOverride || ! $this->configured) {
            return;
        }

        if (isset($config['services'])) {
            foreach (array_keys($config['services']) as $service) {
                if (isset($this->services[$service])) {
                    throw ContainerModificationsNotAllowedException::fromExistingService($service);
                }
            }
        }

        if (isset($config['aliases'])) {
            foreach (array_keys($config['aliases']) as $service) {
                if (isset($this->services[$service])) {
                    throw ContainerModificationsNotAllowedException::fromExistingService($service);
                }
            }
        }

        if (isset($config['invokables'])) {
            foreach (array_keys($config['invokables']) as $service) {
                if (isset($this->services[$service])) {
                    throw ContainerModificationsNotAllowedException::fromExistingService($service);
                }
            }
        }

        if (isset($config['factories'])) {
            foreach (array_keys($config['factories']) as $service) {
                if (isset($this->services[$service])) {
                    throw ContainerModificationsNotAllowedException::fromExistingService($service);
                }
            }
        }

        if (isset($config['delegators'])) {
            foreach (array_keys($config['delegators']) as $service) {
                if (isset($this->services[$service])) {
                    throw ContainerModificationsNotAllowedException::fromExistingService($service);
                }
            }
        }

        if (isset($config['shared'])) {
            foreach (array_keys($config['shared']) as $service) {
                if (isset($this->services[$service])) {
                    throw ContainerModificationsNotAllowedException::fromExistingService($service);
                }
            }
        }

        if (isset($config['lazy_services']['class_map'])) {
            foreach (array_keys($config['lazy_services']['class_map']) as $service) {
                if (isset($this->services[$service])) {
                    throw ContainerModificationsNotAllowedException::fromExistingService($service);
                }
            }
        }
    }

    /**
     * Assuming that the alias name is valid (see above) resolve/add it.
     *
     * This is done differently from bulk mapping aliases for performance reasons, as the
     * algorithms for mapping a single item efficiently are different from those of mapping
     * many.
     */
    private function mapAliasToTarget(string $alias, string $target): void
    {
        // $target is either an alias or something else
        // if it is an alias, resolve it
        $this->aliases[$alias] = $this->aliases[$target] ?? $target;

        // a self-referencing alias indicates a cycle
        if ($alias === $this->aliases[$alias]) {
            throw CyclicAliasException::fromCyclicAlias($alias, $this->aliases);
        }

        // finally we have to check if existing incomplete alias definitions
        // exist which can get resolved by the new alias
        if (in_array($alias, $this->aliases)) {
            $r = array_intersect($this->aliases, [$alias]);
            // found some, resolve them
            foreach ($r as $name => $service) {
                $this->aliases[$name] = $target;
            }
        }
    }

    /**
     * Assuming that all provided alias keys are valid resolve them.
     *
     * This function maps $this->aliases in place.
     *
     * This algorithm is an adaptated version of Tarjans Strongly
     * Connected Components. Instead of returning the strongly
     * connected components (i.e. cycles in our case), we throw.
     * If nodes are not strongly connected (i.e. resolvable in
     * our case), they get resolved.
     *
     * This algorithm is fast for mass updates through configure().
     * It is not appropriate if just a single alias is added.
     *
     * @see mapAliasToTarget above
     */
    private function mapAliasesToTargets(): void
    {
        $tagged = [];
        foreach ($this->aliases as $alias => $target) {
            if (isset($tagged[$alias])) {
                continue;
            }

            $tCursor = $this->aliases[$alias];
            $aCursor = $alias;
            if ($aCursor === $tCursor) {
                throw CyclicAliasException::fromCyclicAlias($alias, $this->aliases);
            }
            if (! isset($this->aliases[$tCursor])) {
                continue;
            }

            $stack = [];

            while (isset($this->aliases[$tCursor])) {
                $stack[] = $aCursor;
                if ($aCursor === $this->aliases[$tCursor]) {
                    throw CyclicAliasException::fromCyclicAlias($alias, $this->aliases);
                }
                $aCursor = $tCursor;
                $tCursor = $this->aliases[$tCursor];
            }

            $tagged[$aCursor] = true;

            foreach ($stack as $alias) {
                if ($alias === $tCursor) {
                    throw CyclicAliasException::fromCyclicAlias($alias, $this->aliases);
                }
                $this->aliases[$alias] = $tCursor;
                $tagged[$alias]        = true;
            }
        }
    }

    /**
     * Instantiate abstract factories in order to avoid checks during service construction.
     *
     * @param class-string<AbstractFactoryInterface>|AbstractFactoryInterface $abstractFactory
     */
    private function resolveAbstractFactoryInstance(string|AbstractFactoryInterface $abstractFactory): void
    {
        if (is_string($abstractFactory)) {
            // Cached string factory name
            if (! isset($this->cachedAbstractFactories[$abstractFactory])) {
                $this->cachedAbstractFactories[$abstractFactory] = new $abstractFactory();
            }

            $abstractFactory = $this->cachedAbstractFactories[$abstractFactory];
        }

        $abstractFactoryObjHash                           = spl_object_hash($abstractFactory);
        $this->abstractFactories[$abstractFactoryObjHash] = $abstractFactory;
    }

    /**
     * Check if a static service or factory exists for the given name.
     */
    private function staticServiceOrFactoryCanCreate(string $name): bool
    {
        if (isset($this->services[$name]) || isset($this->factories[$name])) {
            return true;
        }

        $resolvedName = $this->aliases[$name] ?? $name;
        if ($resolvedName !== $name) {
            return $this->staticServiceOrFactoryCanCreate($resolvedName);
        }

        return false;
    }

    /**
     * Check if an abstract factory exists that can create a service for the given name.
     */
    private function abstractFactoryCanCreate(string $name): bool
    {
        foreach ($this->abstractFactories as $abstractFactory) {
            if ($abstractFactory->canCreate($this->creationContext, $name)) {
                return true;
            }
        }

        $resolvedName = $this->aliases[$name] ?? $name;
        if ($resolvedName !== $name) {
            return $this->abstractFactoryCanCreate($resolvedName);
        }

        return false;
    }

    /**
     * @param class-string<DelegatorFactoryInterface>|DelegatorCallable|DelegatorFactoryInterface $delegatorFactory
     * @return DelegatorCallable
     */
    private function resolveDelegatorFactory(DelegatorFactoryInterface|string|callable $delegatorFactory): callable
    {
        if ($delegatorFactory === LazyServiceFactory::class) {
            return $this->createLazyServiceDelegatorFactory();
        }

        if (is_callable($delegatorFactory)) {
            return $delegatorFactory;
        }

        if (is_string($delegatorFactory)) {
            $delegatorFactory = new $delegatorFactory();
        }

        return $delegatorFactory;
    }
}
