<?php
namespace Omeka\Service;

use Omeka\Stdlib\Environment;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class EnvironmentFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Environment(
            $services->get('Omeka\Connection'),
            $services->get('Omeka\Settings')
        );
    }
}
