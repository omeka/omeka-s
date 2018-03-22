<?php
namespace Omeka\Service\Controller\Admin;

use Interop\Container\ContainerInterface;
use Omeka\Controller\Admin\ItemController;
use Zend\ServiceManager\Factory\FactoryInterface;

class ItemControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ItemController(
            $services->get('Omeka\Media\Ingester\Manager')
        );
    }
}
