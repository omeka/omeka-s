<?php
namespace Omeka\Job\Strategy;

use Omeka\Model\Entity\Job;

class PhpCliStrategy extends AbstractStrategy
{
    public function send(Job $job)
    {
        $entityManager = $this->getServiceLocator()->get('Omeka\EntityManager');

        $script = OMEKA_PATH . '/data/scripts/perform-job.php';

        $command = sprintf(
            '%s %s -j %s',
            escapeshellcmd($this->getPhpcliPath()),
            escapeshellarg($script),
            escapeshellarg($job->getId())
        );

        exec(sprintf('%s > /dev/null 2>&1 &', $command));
    }

    /**
     * Get the path to the PHP-CLI executable.
     *
     * @return string
     */
    public function getPhpcliPath()
    {
        $config = $this->getServiceLocator()->get('Config');
        if (isset($config['jobs']['phpcli_path']) && $config['jobs']['phpcli_path']) {
             $phpcliPath = $config['jobs']['phpcli_path'];
        } else {
            $phpcliPath = trim(shell_exec('which php'));
        }
        return $phpcliPath;
    }
}
