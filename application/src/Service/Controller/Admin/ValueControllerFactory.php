<?php
namespace Omeka\Service\Controller\Admin;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Omeka\Controller\Admin\ValueController;

class ValueControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedNamed, array $options = null)
    {
        return new ValueController(
            $services->get('Omeka\EntityManager')
        );
    }
}
