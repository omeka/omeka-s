<?php
namespace Omeka\Service\Controller\Admin;

use Interop\Container\ContainerInterface;
use Omeka\Controller\Admin\OptionSidebarController;
use Laminas\ServiceManager\Factory\FactoryInterface;

class OptionSidebarControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, ?array $options = null)
    {
        return new OptionSidebarController(
            $services->get('Omeka\OptionSidebar')
        );
    }
}
