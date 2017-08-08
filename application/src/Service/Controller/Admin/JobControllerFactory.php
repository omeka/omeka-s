<?php
namespace Omeka\Service\Controller\Admin;

use Interop\Container\ContainerInterface;
use Omeka\Controller\Admin\JobController;
use Zend\ServiceManager\Factory\FactoryInterface;

class JobControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new JobController($services->get('Omeka\Job\Dispatcher'));
    }
}
