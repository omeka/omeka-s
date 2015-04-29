<?php
namespace Omeka\Job\Strategy;

use DateTime;
use Omeka\Job\Exception;
use Omeka\Job\Strategy\StrategyInterface;
use Omeka\Entity\Job;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class SynchronousStrategy implements StrategyInterface
{
    use ServiceLocatorAwareTrait;

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

        if (Job::STATUS_STOPPING == $job->getStatus()) {
            $job->setStatus(Job::STATUS_STOPPED);
        } else {
            $job->setStatus(Job::STATUS_COMPLETED);
        }
        $job->setEnded(new DateTime('now'));
        $entityManager->flush();
    }
}
