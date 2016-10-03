<?php

namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\Params;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

/**
 * Service factory for the params view helper.
 */
class ParamsFactory implements FactoryInterface
{
    /**
     * Create and return the params view helper
     *
     * @return Params
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Params($services->get('ControllerPluginManager')->get('Params'));
    }
}
