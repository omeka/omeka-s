<?php
namespace Omeka\Job\Strategy;

use Omeka\Model\Entity\Job;

class PhpCliStrategy extends AbstractStrategy
{
    public function send(Job $job)
    {
        $entityManager = $this->getServiceLocator()->get('Omeka\EntityManager');

        $executable = trim(shell_exec('which php'));
        $script = OMEKA_PATH . '/data/scripts/perform-job.php';

        $command = sprintf(
            '%s %s -j %s',
            escapeshellcmd($executable),
            escapeshellarg($script),
            escapeshellarg($job->getId())
        );

        exec(sprintf('%s > /dev/null 2>&1 &', $command));
    }
}
