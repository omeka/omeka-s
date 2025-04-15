<?php

declare(strict_types=1);

namespace Laminas\ServiceManager\Tool\AheadOfTimeFactoryCompiler;

use Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory;
use Laminas\ServiceManager\Exception\InvalidArgumentException;
use Laminas\ServiceManager\Tool\FactoryCreatorInterface;

use function array_filter;
use function array_key_exists;
use function class_exists;
use function enum_exists;
use function is_array;
use function is_string;
use function sprintf;

use const ARRAY_FILTER_USE_BOTH;
use const PHP_VERSION_ID;

final class AheadOfTimeFactoryCompiler implements AheadOfTimeFactoryCompilerInterface
{
    public function __construct(
        private readonly FactoryCreatorInterface $factoryCreator,
    ) {
    }

    public function compile(array $config): array
    {
        $servicesRegisteredByReflectionBasedFactory = $this->extractServicesRegisteredByReflectionBasedFactory(
            $config
        );

        $compiledFactories = [];

        foreach ($servicesRegisteredByReflectionBasedFactory as $service => [$containerConfigurationKey, $aliases]) {
            $compiledFactories[] = new AheadOfTimeCompiledFactory(
                $service,
                $containerConfigurationKey,
                $this->factoryCreator->createFactory($service, $aliases),
            );
        }

        return $compiledFactories;
    }

    /**
     * @return array<class-string,array{non-empty-string,array<string,string>}>
     */
    private function extractServicesRegisteredByReflectionBasedFactory(array $config): array
    {
        $services = [];

        foreach ($config as $key => $entry) {
            if (! is_string($key) || $key === '' || ! is_array($entry)) {
                continue;
            }

            if (! array_key_exists('factories', $entry) || ! is_array($entry['factories'])) {
                continue;
            }

            /** @var array<string,ReflectionBasedAbstractFactory|class-string<ReflectionBasedAbstractFactory>> $servicesUsingReflectionBasedFactory */
            $servicesUsingReflectionBasedFactory = array_filter(
                $entry['factories'],
                static fn(mixed $value): bool =>
                    $value === ReflectionBasedAbstractFactory::class
                    || $value instanceof ReflectionBasedAbstractFactory,
                ARRAY_FILTER_USE_BOTH,
            );

            if ($servicesUsingReflectionBasedFactory === []) {
                continue;
            }

            foreach ($servicesUsingReflectionBasedFactory as $service => $factory) {
                if (! $this->canServiceBeUsedWithReflectionBasedFactory($service)) {
                    throw new InvalidArgumentException(sprintf(
                        'Configured service "%s" using the `ReflectionBasedAbstractFactory` does not exist or does'
                        . ' not refer to an actual class.',
                        $service
                    ));
                }

                if (isset($services[$service])) {
                    throw new InvalidArgumentException(sprintf(
                        'The exact same service "%s" is registered in (at least) two service-/plugin-managers: %s, %s',
                        $service,
                        $services[$service][0],
                        $key
                    ));
                }

                $aliases = [];
                if ($factory instanceof ReflectionBasedAbstractFactory && $factory->aliases !== []) {
                    $aliases = $factory->aliases;
                }

                $services[$service] = [$key, $aliases];
            }
        }

        return $services;
    }

    /**
     * Starting with PHP 8.1, `class_exists` resolves to `true` for enums.
     *
     * @link https://3v4l.org/FY7eg
     *
     * @psalm-assert-if-true class-string $service
     */
    private function canServiceBeUsedWithReflectionBasedFactory(string $service): bool
    {
        if (! class_exists($service)) {
            return false;
        }

        if (PHP_VERSION_ID < 80100) {
            return true;
        }

        return ! enum_exists($service);
    }
}
