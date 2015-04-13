<?php
namespace Omeka\Service;

use Omeka\Job\Dispatcher;
use Omeka\Service\Exception;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class JobDispatcherFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $strategy = $serviceLocator->get('Omeka\JobDispatchStrategy');
        return new Dispatcher($strategy);
    }
}
