<?php

declare(strict_types=1);

namespace Laminas\ServiceManager\Command;

use Laminas\ServiceManager\Tool\ConfigDumperInterface;
use Psr\Container\ContainerInterface;

use function assert;

/**
 * @internal Factories are not meant to be used in any upstream projects.
 */
final class ConfigDumperCommandFactory
{
    public function __invoke(ContainerInterface $container): ConfigDumperCommand
    {
        $dumper = $container->get(ConfigDumperInterface::class);
        assert($dumper instanceof ConfigDumperInterface);
        return new ConfigDumperCommand($dumper);
    }
}
