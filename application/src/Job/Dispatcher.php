<?php
namespace Omeka\Job;

use Omeka\Job\Strategy\StrategyInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class Dispatcher implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * @var StrategyInterface
     */
    protected $shortRunningStrategy;

    /**
     * @var StrategyInterface
     */
    protected $longRunningStrategy;

    /**
     * Set the short- and long-running strategies.
     *
     * @param StrategyInterface $shortRunningStrategy
     * @param StrategyInterface $longRunningStrategy
     */
    public function __construct(
        StrategyInterface $shortRunningStrategy,
        StrategyInterface $longRunningStrategy
    ) {
        $this->shortRunningStrategy = $shortRunningStrategy;
        $this->longRunningStrategy = $longRunningStrategy;
    }

    /**
     * @return StrategyInterface
     */
    public function getShortRunningStrategy()
    {
        return $this->shortRunningStrategy;
    }

    /**
     * @return StrategyInterface
     */
    public function getLongRunningStrategy()
    {
        return $this->shortRunningStrategy;
    }

    /**
     * Dispatch a job via the specified strategy.
     *
     * @param StrategyInterface $strategy
     * @param string $class
     * @param mixed $args
     */
    public function dispatch(StrategyInterface $strategy, $class, $args = null)
    {
        if (!is_subclass_of($class, 'Omeka\Job\JobInterface')) {
            throw new Exception\InvalidArgumentException;
        }
        $strategy->setServiceLocator($this->getServiceLocator());
        $strategy->send($class, $args);
    }

    /**
     * Dispatch a job via the configured short-running strategy.
     *
     * @param string $class
     * @param mixed $args
     */
    public function dispatchShortRunning($class, $args = null)
    {
        $this->dispatch($this->getShortRunningStrategy(), $class, $args);
    }

    /**
     * Dispatch a job via the configured long-running strategy.
     *
     * @param string $class
     * @param mixed $args
     */
    public function dispatchLongRunning($class, $args = null)
    {
        $this->dispatch($this->getLongRunningStrategy(), $class, $args);
    }
}
