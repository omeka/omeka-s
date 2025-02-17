<?php

namespace CustomVocab\Service\Controller\Admin;

use CustomVocab\Controller\Admin\IndexController;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class IndexControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new IndexController($services->get('CustomVocab\ImportExport'));
    }
}
