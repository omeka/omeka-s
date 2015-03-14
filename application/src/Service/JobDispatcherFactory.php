<?php
namespace Omeka\Service;

use Omeka\Job\Dispatcher;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class JobDispatcherFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');
        $strategy = $serviceLocator->get($config['jobs']['strategy']);
        return new Dispatcher($strategy);
    }
}
