<?php

declare(strict_types=1);

namespace Laminas\Validator;

use Laminas\ServiceManager\ServiceManager;

/** @psalm-import-type ServiceManagerConfiguration from ServiceManager */
final class ConfigProvider
{
    /**
     * Return configuration for this component.
     *
     * @return array{dependencies: ServiceManagerConfiguration}
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencyConfig(),
        ];
    }

    /**
     * Return dependency mappings for this component.
     *
     * @return ServiceManagerConfiguration
     */
    public function getDependencyConfig(): array
    {
        return [
            'aliases'   => [
                'ValidatorManager' => ValidatorPluginManager::class,
            ],
            'factories' => [
                ValidatorChainFactory::class  => ValidatorChainFactoryFactory::class,
                ValidatorPluginManager::class => ValidatorPluginManagerFactory::class,
            ],
        ];
    }
}
