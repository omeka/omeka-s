<?php

namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\NavigationLink;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Service factory for the navigationLink view helper.
 */
class NavigationLinkFactory implements FactoryInterface
{
    /**
     * Create and return the navigationLink view helper
     *
     * @param ServiceLocatorInterface $viewServiceLocator
     * @return NavigationLink
     */
    public function createService(ServiceLocatorInterface $viewServiceLocator)
    {
        $serviceLocator = $viewServiceLocator->getServiceLocator();
        return new NavigationLink($serviceLocator->get('Omeka\Site\NavigationLinkManager'));
    }
}
