<?php
namespace Omeka\Service;

use Omeka\Stdlib\FulltextSearch;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class FulltextSearchFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new FulltextSearch($services->get('Omeka\EntityManager'));
    }
}
