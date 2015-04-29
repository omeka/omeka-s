<?php
namespace Omeka\Job;

use Omeka\Entity\Job;
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
     * @param mixed $default
     * @return mixed|null
     */
    public function getArg($name, $default = null)
    {
        $args = $this->job->getArgs();
        if (!is_array($args)) {
            return $default;
        }
        if (!array_key_exists($name, $args)) {
            return $default;
        }
        return $args[$name];
    }

    /**
     * Check if this job should stop.
     *
     * Typically called from within an iteration and followed by whatever logic
     * is needed to gracefully clean up the job, in turn followed by a break out
     * of the iteration and no further work.
     *
     * Refreshes the job entity since the process that sets STATUS_STOPPING is
     * not necessarily the same process that this job is running on.
     *
     * @return bool
     */
    public function shouldStop()
    {
        $entityManager = $this->getServiceLocator()->get('Omeka\EntityManager');
        $entityManager->refresh($this->job);
        return Job::STATUS_STOPPING == $this->job->getStatus();
    }
}
