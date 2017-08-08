<?php
namespace Omeka\Service\ControllerPlugin;

use Interop\Container\ContainerInterface;
use Omeka\Mvc\Controller\Plugin\JobDispatcher;
use Zend\ServiceManager\Factory\FactoryInterface;

class JobDispatcherFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new JobDispatcher($services->get('Omeka\Job\Dispatcher'));
    }
}
