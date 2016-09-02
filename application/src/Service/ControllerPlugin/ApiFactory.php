<?php
namespace Omeka\Service\ControllerPlugin;

use Interop\Container\ContainerInterface;
use Omeka\Mvc\Controller\Plugin\Api;
use Zend\ServiceManager\Factory\FactoryInterface;

class ApiFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Api($services->get('Omeka\ApiManager'));
    }
}
