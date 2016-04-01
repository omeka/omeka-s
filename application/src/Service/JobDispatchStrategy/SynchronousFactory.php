<?php
namespace Omeka\Service\JobDispatchStrategy;

use Omeka\Job\Strategy\SynchronousStrategy;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class SynchronousFactory implements FactoryInterface
{
    /**
     * Create the PhpCli strategy service.
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return SynchronousStrategy
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new SynchronousStrategy($serviceLocator);
    }
}
