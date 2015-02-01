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

        $shortRunningStrategy = $config['jobs']['short_running_strategy'];
        $longRunningStrategy = $config['jobs']['long_running_strategy'];

        return new Dispatcher(new $shortRunningStrategy, new $longRunningStrategy);
    }
}
