<?php
namespace Omeka\Service;

use Omeka\Stdlib\Browse;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class BrowseFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Browse($services);
    }
}
