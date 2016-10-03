<?php
namespace Omeka\Service\Controller;

use Interop\Container\ContainerInterface;
use Omeka\Controller\InstallController;
use Zend\ServiceManager\Factory\FactoryInterface;

class InstallControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new InstallController($services->get('Omeka\Installer'));
    }
}
