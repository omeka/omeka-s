<?php
namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\Status;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

/**
 * Service factory for the status view helper.
 */
class StatusFactory implements FactoryInterface
{
    /**
     * Create and return the status view helper
     *
     * @return Setting
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Status($services->get('Omeka\Status'));
    }
}
