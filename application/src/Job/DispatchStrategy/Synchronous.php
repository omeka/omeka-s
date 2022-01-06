<?php
namespace Omeka\Job\DispatchStrategy;

use DateTime;
use Doctrine\ORM\EntityManager;
use Laminas\Log\Logger;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Omeka\Entity\Job;

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

    public function send(Job $job)
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->serviceLocator->get('Omeka\EntityManager');
        $logger = $this->serviceLocator->get('Omeka\Logger');
        register_shutdown_function([$this, 'handleFatalError'], $job, $entityManager, $logger);

        $job->setStatus(Job::STATUS_IN_PROGRESS);
        $entityManager->flush();

        $class = $job->getClass();
        $jobClass = new $class($job, $this->serviceLocator);
        $jobClass->perform();

        // Make sure we only flush this Job and nothing else.
        // TODO Ideally, the job itself should be managed by a distinct entity manager.
        $entityManager->clear();

        // Reload job that may have been updated during process, but keep the
        // logs since the local detached job object is up-to-date.
        $jobEntity = $entityManager->find(Job::class, $job->getId());
        if ($jobEntity) {
            $jobEntity->setLog($job->getLog());
            $job = $jobEntity;
        }

        if (Job::STATUS_STOPPING == $job->getStatus()) {
            $job->setStatus(Job::STATUS_STOPPED);
        } else {
            $job->setStatus(Job::STATUS_COMPLETED);
        }
        $job->setEnded(new DateTime('now'));
        $entityManager->flush();
    }

    /**
     * Log status and message if job terminates with a fatal error
     *
     * @param Job $job
     * @param EntityManager $entityManager
     * @param Logger $logger
     */
    public function handleFatalError(Job $job, EntityManager $entityManager, Logger $logger)
    {
        $lastError = error_get_last();
        if ($lastError) {
            $errors = [E_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR];
            // Make sure we only flush this Job and nothing else.
            $entityManager->clear();

            if (in_array($lastError['type'], $errors)) {
                // Reload job that may have been updated during process, but
                // keep the logs since the job object itself is up-to-date.
                $jobEntity = $entityManager->find(Job::class, $job->getId());
                $jobEntity->setLog($job->getLog());
                $entityManager->flush();

                $job = $jobEntity;

                $job->setStatus(Job::STATUS_ERROR);
                $job->addLog(vsprintf(
                    "Fatal error: %s\nin %s on line %s", // @translate
                    [
                        $lastError['message'],
                        $lastError['file'],
                        $lastError['line'],
                    ]
                ));
                $entityManager->flush();
            }
            // Log other errors according to the config for severity.
            else {
                $logger->warn(vsprintf(
                    "Warning: %s\nin %s on line %s", // @translate
                    [
                        $lastError['message'],
                        $lastError['file'],
                        $lastError['line'],
                    ]
                ));
            }
        }
    }
}
