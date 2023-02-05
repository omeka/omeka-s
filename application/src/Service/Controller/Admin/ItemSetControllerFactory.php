<?php
namespace Omeka\Service\Controller\Admin;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Omeka\Controller\Admin\ItemSetController;

class ItemSetControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ItemSetController(
            $services->get('Omeka\EntityManager')
        );
    }
}
