<?php
namespace Omeka\Service\ControllerPlugin;

use Interop\Container\ContainerInterface;
use Omeka\Mvc\Controller\Plugin\Paginator;
use Zend\ServiceManager\Factory\FactoryInterface;

class PaginatorFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Paginator($services->get('ViewHelperManager'));
    }
}
