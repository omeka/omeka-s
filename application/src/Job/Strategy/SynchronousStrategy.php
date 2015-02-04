<?php
namespace Omeka\Job\Strategy;

use DateTime;
use Omeka\Job\Exception;
use Omeka\Model\Entity\Job;

class SynchronousStrategy extends AbstractStrategy
{
    /**
     * {@inheritDoc}
     */
    public function send(Job $job)
    {
        $class = $job->getClass();
        if (!is_subclass_of($class, 'Omeka\Job\JobInterface')) {
            throw new Exception\InvalidArgumentException(sprintf('The job class "%s" does not implement Omeka\Job\JobInterface.', $class));
        }

        $entityManager = $this->getServiceLocator()->get('Omeka\EntityManager');

        $jobClass = new $class;
        $jobClass->setServiceLocator($this->getServiceLocator());
        $jobClass->setArgs($job->getArgs());

        $job->setStatus(Job::STATUS_IN_PROGRESS);
        $entityManager->flush();

        $jobClass->perform();

        $job->setStatus(Job::STATUS_COMPLETED);
        $job->setStopped(new DateTime('now'));
        $entityManager->flush();
    }
}
