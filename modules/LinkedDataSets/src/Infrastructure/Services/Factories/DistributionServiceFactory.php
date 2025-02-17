<?php

declare(strict_types=1);

namespace LinkedDataSets\Infrastructure\Services\Factories;

use LinkedDataSets\Application\Service\DistributionService;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

final class DistributionServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): DistributionService
    {
        return new DistributionService();
    }
}
