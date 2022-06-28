<?php
namespace Omeka\Service\ControllerPlugin;

use Interop\Container\ContainerInterface;
use Omeka\Mvc\Controller\Plugin\Browse;
use Laminas\ServiceManager\Factory\FactoryInterface;

class BrowseFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Browse($services->get('Omeka\Browse'));
    }
}
