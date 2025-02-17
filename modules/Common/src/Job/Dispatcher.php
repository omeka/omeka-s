<?php declare(strict_types=1);

namespace Common\Job;

use DateTime;
use Omeka\Entity\Job;
use Omeka\Job\DispatchStrategy\StrategyInterface;
use Omeka\Log\Writer\Job as JobWriter;

class Dispatcher extends \Omeka\Job\Dispatcher
{
    /**
     * Set a PSR-3 formatter to the job writter.
     *
     * {@inheritDoc}
     * @see \Omeka\Job\Dispatcher::send()
     */
    public function send(Job $job, StrategyInterface $strategy): void
    {
        $writer = new JobWriter($job);
        $writer->setFormatter(new \Common\Log\Formatter\PsrLogSimple);
        $this->logger->addWriter($writer);

        // Copy of  the parent method.

        try {
            $strategy->send($job);
        } catch (\Exception $e) {
            $this->logger->err((string) $e);
            $job->setStatus(Job::STATUS_ERROR);
            $job->setEnded(new DateTime('now'));

            // Account for "inside Doctrine" errors that close the EM
            if ($this->entityManager->isOpen()) {
                $entityManager = $this->entityManager;
            } else {
                $entityManager = $this->getNewEntityManager($this->entityManager);
            }

            $entityManager->clear();
            $entityManager->merge($job);
            $entityManager->flush();
        }
    }
}
