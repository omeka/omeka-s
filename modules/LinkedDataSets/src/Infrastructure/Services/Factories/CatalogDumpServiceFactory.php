<?php

namespace LinkedDataSets\Infrastructure\Services\Factories;

use Laminas\ServiceManager\Factory\FactoryInterface;
use LinkedDataSets\Application\Service\CatalogDumpService;
use Psr\Container\ContainerInterface;

class CatalogDumpServiceFactory implements FactoryInterface
{
    public function __invoke(
        ContainerInterface $serviceLocator,
        $requestedName,
        array $options = null
    ): CatalogDumpService {
        return new CatalogDumpService(
            $serviceLocator->get('Omeka\Logger'),
            $serviceLocator->get('LDS\UriHelper'),
        );
    }
}
