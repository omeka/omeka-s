<?php

namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\Params;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Service factory for the params view helper.
 */
class ParamsFactory implements FactoryInterface
{
    /**
     * Create and return the params view helper
     *
     * @param ServiceLocatorInterface $viewServiceLocator
     * @return Params
     */
    public function createService(ServiceLocatorInterface $viewServiceLocator)
    {
        $serviceLocator = $viewServiceLocator->getServiceLocator();
        return new Params($serviceLocator->get('ControllerPluginManager')->get('Params'));
    }
}
