<?php

namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\Trigger;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

/**
 * Service factory for the trigger view helper.
 */
class TriggerFactory implements FactoryInterface
{
    /**
     * Create and return the trigger view helper
     *
     * @return Trigger
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Trigger($services->get('EventManager'), $services->get('ControllerPluginManager'));
    }
}
