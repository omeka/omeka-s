<?php
namespace Omeka\Service\Controller\Admin;

use Interop\Container\ContainerInterface;
use Omeka\Controller\Admin\SystemInfoController;
use Zend\ServiceManager\Factory\FactoryInterface;

class SystemInfoControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new SystemInfoController($services->get('Omeka\Connection'));
    }
}
