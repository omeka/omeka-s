<?php
namespace Omeka\Service\Form\Element;

use Interop\Container\ContainerInterface;
use Omeka\Form\Element\SitePageSelect;
use Laminas\ServiceManager\Factory\FactoryInterface;

class SitePageSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $element = new SitePageSelect;
        $currentSite = $services->get('ControllerPluginManager')->get('currentSite');
        $element->setSite($currentSite());
        return $element;
    }
}
