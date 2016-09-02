<?php

namespace Omeka\Service;

use Omeka\View\Strategy\ApiJsonStrategy;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

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
     * @return ApiJsonStrategy
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $jsonRenderer = $serviceLocator->get('Omeka\ViewApiJsonRenderer');
        $jsonStrategy = new ApiJsonStrategy($jsonRenderer);
        return $jsonStrategy;
    }
}
