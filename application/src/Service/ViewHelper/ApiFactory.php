<?php

namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\Api;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Service factory for the api view helper.
 */
class ApiFactory implements FactoryInterface
{
    /**
     * Create and return the api view helper
     *
     * @param ServiceLocatorInterface $viewServiceLocator
     * @return Api
     */
    public function createService(ServiceLocatorInterface $viewServiceLocator)
    {
        $serviceLocator = $viewServiceLocator->getServiceLocator();
        return new Api($serviceLocator->get('Omeka\ApiManager'));
    }
}
