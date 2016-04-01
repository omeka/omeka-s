<?php

namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\Trigger;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Service factory for the trigger view helper.
 */
class TriggerFactory implements FactoryInterface
{
    /**
     * Create and return the trigger view helper
     *
     * @param ServiceLocatorInterface $viewServiceLocator
     * @return Trigger
     */
    public function createService(ServiceLocatorInterface $viewServiceLocator)
    {
        $serviceLocator = $viewServiceLocator->getServiceLocator();
        return new Trigger($serviceLocator->get('EventManager'), $serviceLocator->get('Application'));
    }
}
