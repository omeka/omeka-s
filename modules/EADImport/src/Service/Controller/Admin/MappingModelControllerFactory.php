<?php

namespace EADImport\Service\Controller\Admin;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use EADImport\Controller\Admin\MappingModelController;

class MappingModelControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $mappingModelController = new MappingModelController($serviceLocator);

        return $mappingModelController;
    }
}
