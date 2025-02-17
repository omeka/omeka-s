<?php

declare(strict_types=1);

namespace LinkedDataSets\Infrastructure\Services\Factories;

use Laminas\ServiceManager\Factory\FactoryInterface;
use LinkedDataSets\Infrastructure\Services\FileCompressionService;
use Psr\Container\ContainerInterface;

final class FileCompressionServiceFactory implements FactoryInterface
{
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): FileCompressionService {
        return new FileCompressionService();
    }
}
