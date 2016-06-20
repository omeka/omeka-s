<?php
namespace Omeka\Service;

use Omeka\Mvc\Status;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Status object factory.
 */
class StatusFactory implements FactoryInterface
{
    /**
     * Create the status service.
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return Status
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new Status($serviceLocator);
    }
}
