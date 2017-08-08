<?php
namespace Omeka\Job;

use DateTime;
use Doctrine\ORM\EntityManager;
use Omeka\Job\DispatchStrategy\StrategyInterface;
use Omeka\Entity\Job;
use Omeka\Log\Writer\Job as JobWriter;
use Zend\Authentication\AuthenticationService;
use Zend\Log\Logger;

class Dispatcher
{
    /**
     * @var StrategyInterface
     */
    protected $dispatchStrategy;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var AuthenticationService
     */
    protected $auth;

    /**
     * Set the dispatch strategy.
     *
     * @param StrategyInterface $dispatchStrategy
     * @param EntityManager $entityManager
     * @param Logger $logger
     * @param AuthenticationService $auth
     */
    public function __construct(StrategyInterface $dispatchStrategy, EntityManager $entityManager,
        Logger $logger, AuthenticationService $auth)
    {
        $this->dispatchStrategy = $dispatchStrategy;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->auth = $auth;
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
        if (!class_exists($class)) {
            throw new Exception\InvalidArgumentException(sprintf('The job class "%s" does not exist.', $class));
        }
        if (!is_subclass_of($class, 'Omeka\Job\JobInterface')) {
            throw new Exception\InvalidArgumentException(sprintf('The job class "%s" does not implement Omeka\Job\JobInterface.', $class));
        }

        $job = new Job;
        $job->setStatus(Job::STATUS_STARTING);
        $job->setClass($class);
        $job->setArgs($args);
        $job->setOwner($this->auth->getIdentity());
        $this->entityManager->persist($job);
        $this->entityManager->flush();

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
        $this->logger->addWriter(new JobWriter($job));
        try {
            $strategy->send($job);
        } catch (\Exception $e) {
            $this->logger->err((string) $e);
            $job->setStatus(Job::STATUS_ERROR);
            $job->setEnded(new DateTime('now'));

            // Account for "inside Doctrine" errors that close the EM
            if ($this->entityManager->isOpen()) {
                $entityManager = $this->entityManager;
            } else {
                $entityManager = $this->getNewEntityManager($this->entityManager);
            }

            $entityManager->clear();
            $entityManager->merge($job);
            $entityManager->flush();
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
        $job = $this->entityManager->find('Omeka\Entity\Job', $jobId);
        if (!$job) {
            throw new Exception\InvalidArgumentException(sprintf('The job ID "%s" is invalid.', $jobId));
        }
        $job->setStatus(Job::STATUS_STOPPING);
        $this->entityManager->flush();
    }

    /**
     * Get a new EntityManager sharing the settings of an old one.
     *
     * Internal Doctrine errors "close" the EntityManager and we can never use it again, so we need
     * to create a new one if we want to save anything after one of those kinds of errors.
     *
     * @param EntityManager $entityManager
     * @return EntityManager
     */
    private function getNewEntityManager(EntityManager $entityManager)
    {
        return EntityManager::create(
            $entityManager->getConnection(),
            $entityManager->getConfiguration(),
            $entityManager->getEventManager()
        );
    }
}
