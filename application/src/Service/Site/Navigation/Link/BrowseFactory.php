<?php
namespace Omeka\Service\Site\Navigation\Link;

use Omeka\Site\Navigation\Link\Browse;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class BrowseFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Browse($services->get('ViewHelperManager'));
    }
}
