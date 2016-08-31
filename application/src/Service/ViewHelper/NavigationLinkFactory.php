<?php

namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\NavigationLink;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

/**
 * Service factory for the navigationLink view helper.
 */
class NavigationLinkFactory implements FactoryInterface
{
    /**
     * Create and return the navigationLink view helper
     *
     * @return NavigationLink
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new NavigationLink($services->get('Omeka\Site\NavigationLinkManager'));
    }
}
