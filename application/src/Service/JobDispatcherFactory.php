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
        $config = $serviceLocator->get('Config');
        if (!isset($config['jobs']['strategy'])) {
            throw new Exception\ConfigException('Missing jobs configuration');
        }
        $strategy = $serviceLocator->get($config['jobs']['strategy']);
        return new Dispatcher($strategy);
    }
}
