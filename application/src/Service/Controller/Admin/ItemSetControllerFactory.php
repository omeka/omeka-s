<?php
namespace Omeka\Service\Controller\Admin;

use Interop\Container\ContainerInterface;
use Omeka\Controller\Admin\ItemSetController;
use Zend\ServiceManager\Factory\FactoryInterface;

class ItemSetControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ItemSetController($services->get('Omeka\Job\Dispatcher'));
    }
}
