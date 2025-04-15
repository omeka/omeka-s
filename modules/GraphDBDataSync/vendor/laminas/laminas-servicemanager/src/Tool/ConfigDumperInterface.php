<?php

declare(strict_types=1);

namespace Laminas\ServiceManager\Tool;

use Laminas\ServiceManager\Exception\InvalidArgumentException;

interface ConfigDumperInterface
{
    /**
     * @param array<string,mixed> $config
     * @param class-string $className
     * @return array<string,mixed>
     * @throws InvalidArgumentException For unsupported class-string.
     */
    public function createDependencyConfig(array $config, string $className, bool $ignoreUnresolved = false): array;

    /**
     * @param array<string,mixed> $config
     * @return array<string,mixed>
     * @throws InvalidArgumentException If ConfigAbstractFactory configuration
     *     value is not an array.
     */
    public function createFactoryMappingsFromConfig(array $config): array;

    /**
     * @param array<string,mixed> $config
     * @param class-string $className
     * @return array<string,mixed>
     */
    public function createFactoryMappings(array $config, string $className): array;

    /**
     * @return non-empty-string
     */
    public function dumpConfigFile(array $config): string;
}
