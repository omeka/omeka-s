<?php
namespace Omeka\Service;

use Omeka\Job\Dispatcher;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class JobDispatcherFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $strategy = $serviceLocator->get('Omeka\JobDispatchStrategy');
        $em = $serviceLocator->get('Omeka\EntityManager');
        $logger = $serviceLocator->get('Omeka\Logger');
        $auth = $serviceLocator->get('Omeka\AuthenticationService');
        return new Dispatcher($strategy, $em, $logger, $auth);
    }
}
