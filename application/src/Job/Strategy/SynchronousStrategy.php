<?php
namespace Omeka\Job\Strategy;

use DateTime;
use Omeka\Model\Entity\Job;

class SynchronousStrategy extends AbstractStrategy
{
    public function send(Job $job)
    {
        $entityManager = $this->getServiceLocator()->get('Omeka\EntityManager');

        $class = $job->getClass();
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
