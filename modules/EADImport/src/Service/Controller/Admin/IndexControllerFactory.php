<?php

namespace EADImport\Service\Controller\Admin;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use EADImport\Controller\Admin\IndexController;

class IndexControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $tempDir = $serviceLocator->get('Config')['temp_dir'];
        $indexController = new IndexController($tempDir);

        return $indexController;
    }
}
