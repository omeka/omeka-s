<?php
namespace Omeka\Job\Strategy;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

abstract class AbstractStrategy implements StrategyInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * Inject dependencies.
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->setServiceLocator($serviceLocator);
    }
}
