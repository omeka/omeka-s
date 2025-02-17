<?php

namespace LinkedDataSets\Infrastructure\Services\Factories;

use Laminas\ServiceManager\Factory\FactoryInterface;
use LinkedDataSets\Infrastructure\Helpers\UriHelper;
use Psr\Container\ContainerInterface;

class UriHelperFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        return new UriHelper(
            $serviceLocator->get('ViewHelperManager')
        );
    }
}
