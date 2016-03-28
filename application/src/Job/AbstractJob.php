<?php
namespace Omeka\Job;

use Omeka\Entity\Job;
use Zend\ServiceManager\ServiceLocatorInterface;

abstract class AbstractJob implements JobInterface
{
    /**
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator;

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
     * Queries the database for the Job object since the process that sets
     * STATUS_STOPPING is not necessarily the same process that this job is
     * running on. We're not using the entity manager's refresh method because
     * we can't assume a static Job state during the course of the job.
     *
     * @return bool
     */
    public function shouldStop()
    {
        $entityManager = $this->getServiceLocator()->get('Omeka\EntityManager');
        $dql = 'SELECT j.status FROM Omeka\Entity\Job j WHERE j.id = :id';
        $status = $entityManager->createQuery($dql)
            ->setParameter('id', $this->job->getId())
            ->getSingleScalarResult();
        $this->job->setStatus($status);
        return Job::STATUS_STOPPING === $status;
    }

    /**
     * Set the service locator for this job.
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    /**
     * Get the service locator.
     *
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }
}
