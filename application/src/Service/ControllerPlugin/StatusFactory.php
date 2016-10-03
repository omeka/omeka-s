<?php
namespace Omeka\Service\ControllerPlugin;

use Interop\Container\ContainerInterface;
use Omeka\Mvc\Controller\Plugin\Status;
use Zend\ServiceManager\Factory\FactoryInterface;

class StatusFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Status($services->get('Omeka\Status'));
    }
}
