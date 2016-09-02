<?php
namespace Omeka\Service\JobDispatchStrategy;

use Omeka\Job\Strategy\SynchronousStrategy;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class SynchronousFactory implements FactoryInterface
{
    /**
     * Create the PhpCli strategy service.
     *
     * @return SynchronousStrategy
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        return new SynchronousStrategy($serviceLocator);
    }
}
