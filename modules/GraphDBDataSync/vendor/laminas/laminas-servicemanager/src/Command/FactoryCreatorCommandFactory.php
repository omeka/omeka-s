<?php

declare(strict_types=1);

namespace Laminas\ServiceManager\Command;

use Laminas\ServiceManager\Tool\FactoryCreatorInterface;
use Psr\Container\ContainerInterface;

use function assert;

/**
 * @internal Factories are not meant to be used in any upstream projects.
 */
final class FactoryCreatorCommandFactory
{
    public function __invoke(ContainerInterface $container): FactoryCreatorCommand
    {
        $creator = $container->get(FactoryCreatorInterface::class);
        assert($creator instanceof FactoryCreatorInterface);
        return new FactoryCreatorCommand($creator);
    }
}
