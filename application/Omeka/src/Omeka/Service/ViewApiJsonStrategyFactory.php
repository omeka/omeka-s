<?php

namespace Omeka\Service;

use Omeka\View\Strategy\ApiJsonStrategy;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Service factory for the API JSON view strategy.
 */
class ViewApiJsonStrategyFactory implements FactoryInterface
{
    /**
     * Create and return the JSON view strategy
     *
     * Retrieves the ViewApiJsonRenderer service from the service locator, and
     * injects it into the constructor for the JSON strategy.
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return ApiJsonStrategy
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $jsonRenderer = $serviceLocator->get('Omeka\ViewApiJsonRenderer');
        $jsonStrategy = new ApiJsonStrategy($jsonRenderer);
        return $jsonStrategy;
    }
}
