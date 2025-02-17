<?php
namespace NumericDataTypes\Service\Controller\SiteAdmin\FacetedBrowse;

use NumericDataTypes\Controller\SiteAdmin\FacetedBrowse\IndexController;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class IndexControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new IndexController($services);
    }
}
