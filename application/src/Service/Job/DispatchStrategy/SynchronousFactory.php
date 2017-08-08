<?php
namespace Omeka\Service\Job\DispatchStrategy;

use Omeka\Job\DispatchStrategy\Synchronous;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class SynchronousFactory implements FactoryInterface
{
    /**
     * Create the PhpCli strategy service.
     *
     * @return SynchronousStrategy
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Synchronous($services);
    }
}
