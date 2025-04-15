<?php

declare(strict_types=1);

namespace Laminas\ServiceManager\Command;

use Laminas\ServiceManager\ConfigProvider;
use Laminas\ServiceManager\Tool\AheadOfTimeFactoryCompiler\AheadOfTimeFactoryCompilerInterface;
use Psr\Container\ContainerInterface;

use function assert;
use function is_array;
use function is_iterable;
use function is_string;
use function iterator_to_array;

final class AheadOfTimeFactoryCreatorCommandFactory
{
    public function __invoke(ContainerInterface $container): AheadOfTimeFactoryCreatorCommand
    {
        $aheadOfTimeFactoryCompiler = $container->get(AheadOfTimeFactoryCompilerInterface::class);
        assert($aheadOfTimeFactoryCompiler instanceof AheadOfTimeFactoryCompilerInterface);
        $config = $container->has('config') ? $container->get('config') : [];
        if (! is_iterable($config)) {
            return new AheadOfTimeFactoryCreatorCommand([], '', $aheadOfTimeFactoryCompiler);
        }

        if (! is_array($config)) {
            $config = iterator_to_array($config);
        }

        $factoryTargetPath = $config[ConfigProvider::CONFIGURATION_KEY_FACTORY_TARGET_PATH] ?? '';
        if (! is_string($factoryTargetPath)) {
            $factoryTargetPath = '';
        }

        return new AheadOfTimeFactoryCreatorCommand($config, $factoryTargetPath, $aheadOfTimeFactoryCompiler);
    }
}
