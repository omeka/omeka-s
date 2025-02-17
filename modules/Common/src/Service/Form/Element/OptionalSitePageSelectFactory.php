<?php declare(strict_types=1);

namespace Common\Service\Form\Element;

use Common\Form\Element\OptionalSitePageSelect;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class OptionalSitePageSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $element = new OptionalSitePageSelect();
        $currentSite = $services->get('ControllerPluginManager')->get('currentSite');
        $element->setSite($currentSite());
        return $element;
    }
}
