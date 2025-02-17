<?php

namespace LinkedDataSets\Infrastructure\Services\Factories;

use Laminas\ServiceManager\Factory\FactoryInterface;
use LinkedDataSets\Application\Service\UpdateDistributionService;
use Psr\Container\ContainerInterface;

class UpdateDistributionServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        return new UpdateDistributionService(
            $serviceLocator
        );
    }
}
