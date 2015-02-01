<?php
namespace Omeka\Job;

use DateTime;
use Omeka\Job\Strategy\StrategyInterface;
use Omeka\Model\Entity\Job;
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

        $auth = $this->getServiceLocator()->get('Omeka\AuthenticationService');
        $entityManager = $this->getServiceLocator()->get('Omeka\EntityManager');

        $job = new Job;
        $job->setStatus(Job::STATUS_STARTING);
        $job->setClass($class);
        $job->setArgs($args);
        $job->setOwner($auth->getIdentity());
        $entityManager->persist($job);
        $entityManager->flush();

        $strategy->setServiceLocator($this->getServiceLocator());

        try {
            $strategy->send($job);
        } catch (\Exception $e) {
            $this->getServiceLocator()->get('Omeka\Logger')->err((string) $e);
            $job->setStatus(Job::STATUS_ERROR);
            $job->setStopped(new DateTime('now'));
            $entityManager->flush();
        }
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
