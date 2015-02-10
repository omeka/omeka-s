<?php
namespace Omeka\Job;

use Omeka\Model\Entity\Job;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

abstract class AbstractJob implements JobInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * @var Job
     */
    protected $job;

    /**
     * Inject dependencies.
     *
     * @param Job $job
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function __construct(Job $job, ServiceLocatorInterface $serviceLocator)
    {
        $this->job = $job;
        $this->setServiceLocator($serviceLocator);
    }

    /**
     * Get a Job argument by name.
     *
     * Assumes that the job arguments are an array.
     *
     * @param string $name
     * @return mixed|null
     */
    public function getArg($name)
    {
        $args = $this->job->getArgs();
        if (!is_array($args)) {
            return null;
        }
        if (!array_key_exists($name, $args)) {
            return null;
        }
        return $args[$name];
    }

    /**
     * Check if this job should stop.
     *
     * Refreshes the job entity since the process that sets STATUS_STOPPING is
     * not necessarily the same process that this job is running on. Typically
     * called from within an iteration, followed by self::stop().
     *
     * @return bool
     */
    public function shouldStop()
    {
        $entityManager = $this->getServiceLocator()->get('Omeka\EntityManager');
        $entityManager->refresh($this->job);
        return Job::STATUS_STOPPING == $this->job->getStatus();
    }

    /**
     * Stop this job gracefully.
     *
     * Implement this method to perform cleanup in the event that this job has
     * been flagged to be stopped. Typically called from within an iteration,
     * following self::shouldStop() and followed by a break out of the
     * iteration and no further work.
     */
    public function stop()
    {}
}
