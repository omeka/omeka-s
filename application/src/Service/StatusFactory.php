<?php
namespace Omeka\Service;

use Omeka\Mvc\Status;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

/**
 * Status object factory.
 */
class StatusFactory implements FactoryInterface
{
    /**
     * Create the status service.
     *
     * @return Status
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        return new Status($serviceLocator);
    }
}
