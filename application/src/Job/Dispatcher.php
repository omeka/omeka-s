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
     * Composes a Job entity and uses the configured strategy if no strategy is
     * passed.
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

        $entityManager = $this->getServiceLocator()->get('Omeka\EntityManager');
        $auth = $this->getServiceLocator()->get('Omeka\AuthenticationService');

        $job = new Job;
        $job->setStatus(Job::STATUS_STARTING);
        $job->setClass($class);
        $job->setArgs($args);
        $job->setOwner($auth->getIdentity());
        $entityManager->persist($job);
        $entityManager->flush();

        if (!$strategy) {
            $strategy = $this->getDispatchStrategy();
        }

        $this->send($job, $strategy);
        return $job;
    }

    /**
     * Send a job via a strategy.
     *
     * @param Job $job
     * @param StrategyInterface $strategy
     */
    public function send(Job $job, StrategyInterface $strategy)
    {
        try {
            $strategy->send($job);
        } catch (\Exception $e) {
            $this->getServiceLocator()->get('Omeka\Logger')->err((string) $e);
            $job->setStatus(Job::STATUS_ERROR);
            $job->setEnded(new DateTime('now'));
            $this->getServiceLocator()->get('Omeka\EntityManager')->flush();
        }
    }

    /**
     * Set a job to be stopped.
     *
     * This does nothing but change the job status to STATUS_STOPPING. It's up
     * to individual job implementations to stop performing by listening to the
     * status change, usually from within an iteration.
     *
     * @param int $jobId
     */
    public function stop($jobId)
    {
        $entityManager = $this->getServiceLocator()->get('Omeka\EntityManager');
        $job = $entityManager->find('Omeka\Model\Entity\Job', $jobId);
        if (!$job) {
            throw new Exception\InvalidArgumentException(sprintf('The job ID "%s" is invalid.', $jobId));
        }
        $job->setStatus(Job::STATUS_STOPPING);
        $this->getServiceLocator()->get('Omeka\EntityManager')->flush();
    }
}
