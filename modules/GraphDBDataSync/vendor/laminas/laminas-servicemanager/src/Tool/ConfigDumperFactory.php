<?php

declare(strict_types=1);

namespace Laminas\ServiceManager\Tool;

use Mezzio\Application;
use Psr\Container\ContainerInterface;

use function class_exists;

/**
 * @internal
 */
final class ConfigDumperFactory
{
    public function __invoke(ContainerInterface $container): ConfigDumperInterface
    {
        if ($this->isCommandExecutedInMezzioApplication($container)) {
            return new ConfigDumper($container, ConfigDumper::MEZZIO_CONTAINER_CONFIGURATION);
        }

        return new ConfigDumper($container, ConfigDumper::LAMINAS_MVC_SERVICEMANAGER_CONFIGURATION);
    }

    private function isCommandExecutedInMezzioApplication(ContainerInterface $container): bool
    {
        /**
         * @psalm-suppress UndefinedClass MixedArgument We can't require mezzio due to the amount of additional
         *                                              dependencies we would have to add here.
         */
        return class_exists(Application::class) && $container->has(Application::class);
    }
}
