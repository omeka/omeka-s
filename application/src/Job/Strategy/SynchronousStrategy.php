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
        $entityManager = $this->getServiceLocator()->get('Omeka\EntityManager');

        $job->setStatus(Job::STATUS_IN_PROGRESS);
        $entityManager->flush();

        $class = $job->getClass();
        $jobClass = new $class($job, $this->getServiceLocator());
        $jobClass->perform();

        $job->setStatus(Job::STATUS_COMPLETED);
        $job->setEnded(new DateTime('now'));
        $entityManager->flush();
    }
}
