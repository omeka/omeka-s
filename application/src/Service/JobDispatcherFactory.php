<?php
namespace Omeka\Service;

use Omeka\Job\Dispatcher;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class JobDispatcherFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $strategy = $serviceLocator->get('Omeka\JobDispatchStrategy');
        $em = $serviceLocator->get('Omeka\EntityManager');
        $logger = $serviceLocator->get('Omeka\Logger');
        $auth = $serviceLocator->get('Omeka\AuthenticationService');
        return new Dispatcher($strategy, $em, $logger, $auth);
    }
}
