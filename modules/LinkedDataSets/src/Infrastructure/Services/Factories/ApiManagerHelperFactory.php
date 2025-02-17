<?php

namespace LinkedDataSets\Infrastructure\Services\Factories;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use LinkedDataSets\Infrastructure\Helpers\ApiManagerHelper;

class ApiManagerHelperFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        return new ApiManagerHelper(
            $serviceLocator->get('Omeka\ApiManager'),
        );
    }
}
