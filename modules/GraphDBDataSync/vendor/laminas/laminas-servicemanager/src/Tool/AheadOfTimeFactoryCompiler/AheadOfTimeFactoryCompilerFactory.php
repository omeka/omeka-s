<?php

declare(strict_types=1);

namespace Laminas\ServiceManager\Tool\AheadOfTimeFactoryCompiler;

use Laminas\ServiceManager\Tool\FactoryCreatorInterface;
use Psr\Container\ContainerInterface;

use function assert;

final class AheadOfTimeFactoryCompilerFactory
{
    public function __invoke(ContainerInterface $container): AheadOfTimeFactoryCompilerInterface
    {
        $creator = $container->get(FactoryCreatorInterface::class);
        assert($creator instanceof FactoryCreatorInterface);
        return new AheadOfTimeFactoryCompiler($creator);
    }
}
