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
    protected $dispatchStrategy;

    /**
     * Set the dispatch strategy.
     *
     * @param StrategyInterface $dispatchStrategy
     */
    public function __construct(StrategyInterface $dispatchStrategy)
    {
        $this->dispatchStrategy = $dispatchStrategy;
    }

    /**
     * @return StrategyInterface
     */
    public function getDispatchStrategy()
    {
        return $this->dispatchStrategy;
    }

    /**
     * Dispatch a job.
     *
     * Uses the configured strategy if no strategy is passed.
     *
     * @param string $class
     * @param mixed $args
     * @param StrategyInterface $strategy
     * @return null|Job $job
     */
    public function dispatch($class, $args = null, StrategyInterface $strategy = null)
    {
        if (!is_subclass_of($class, 'Omeka\Job\JobInterface')) {
            throw new Exception\InvalidArgumentException(sprintf('The job class "%s" does not implement Omeka\Job\JobInterface.', $class));
        }

        if (!$strategy) {
            $strategy = $this->getDispatchStrategy();
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

        return $job;
    }
}
