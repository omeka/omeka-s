<?php
namespace Omeka\Service\Site\Navigation\Link;

use Omeka\Site\Navigation\Link\BrowseItemSets;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class BrowseItemSetsFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new BrowseItemSets($services->get('ViewHelperManager'));
    }
}
