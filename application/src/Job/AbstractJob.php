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
}
