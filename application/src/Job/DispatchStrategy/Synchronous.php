<?php
namespace Omeka\Job\DispatchStrategy;

use DateTime;
use Doctrine\ORM\EntityManager;
use Omeka\Entity\Job;
use Zend\ServiceManager\ServiceLocatorInterface;

class Synchronous implements StrategyInterface
{
    /**
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator;

    /**
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    /**
     * {@inheritDoc}
     */
    public function send(Job $job)
    {
        $entityManager = $this->serviceLocator->get('Omeka\EntityManager');
        register_shutdown_function([$this, 'handleFatalError'], $job, $entityManager);

        $job->setStatus(Job::STATUS_IN_PROGRESS);
        $entityManager->flush();

        $class = $job->getClass();
        $jobClass = new $class($job, $this->serviceLocator);
        $jobClass->perform();

        if (Job::STATUS_STOPPING == $job->getStatus()) {
            $job->setStatus(Job::STATUS_STOPPED);
        } else {
            $job->setStatus(Job::STATUS_COMPLETED);
        }
        $job->setEnded(new DateTime('now'));
        $entityManager->clear();
        $entityManager->merge($job);
        $entityManager->flush();
    }

    /**
     * Log status and message if job terminates with a fatal error
     *
     * @param Job $job
     * @param EntityManager $entityManager
     */
    public function handleFatalError(Job $job, EntityManager $entityManager)
    {
        $lastError = error_get_last();
        $errors = [E_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR];
        if ($lastError && in_array($lastError['type'], $errors)) {
            $job->setStatus(Job::STATUS_ERROR);
            $job->addLog(sprintf("Fatal error: %s\nin %s on line %s",
                $lastError['message'],
                $lastError['file'],
                $lastError['line']
            ));

            // Make sure we only flush this Job and nothing else
            $entityManager->clear();
            $entityManager->merge($job);
            $entityManager->flush();
        }
    }
}
