<?php
namespace Omeka\Job\Strategy;

use Omeka\Installation\Task\CheckEnvironmentTask;
use Omeka\Job\Exception;
use Omeka\Job\Strategy\StrategyInterface;
use Omeka\Entity\Job;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class PhpCliStrategy implements StrategyInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * Perform the job in the background.
     *
     * Jobs may need access to variables that are impossible to derive from
     * outside a web context. Here we pass the variables via shell arguments.
     * The perform-job script then sets them to the PHP-CLI context.
     *
     * @todo Pass the server URL, or compents required to set one
     * @see \Zend\View\Helper\BasePath
     * @see \Zend\View\Helper\ServerUrl
     *
     * {@inheritDoc}
     */
    public function send(Job $job)
    {
        $config = $this->getServiceLocator()->get('Config');
        $cli = $this->getServiceLocator()->get('Omeka\Cli');

        if (isset($config['cli']['phpcli_path']) && $config['cli']['phpcli_path']) {
            $phpPath = $cli->validateCommand($config['cli']['phpcli_path']);
            if (false === $phpPath) {
                throw new Exception\RuntimeException('PHP-CLI error: invalid PHP path.');
            }
        } else {
            $phpPath = $cli->getCommandPath('php');
            if (false === $phpPath) {
                throw new Exception\RuntimeException('PHP-CLI error: cannot determine path to PHP.');
            }
        }

        $script = OMEKA_PATH . '/data/scripts/perform-job.php';
        $basePath = $this->getServiceLocator()->get('ViewHelperManager')->get('BasePath');

        $command = sprintf(
            '%s %s --job-id %s --base-path %s',
            escapeshellcmd($phpPath),
            escapeshellarg($script),
            escapeshellarg($job->getId()),
            escapeshellarg($basePath())
        );

        exec(sprintf('%s > /dev/null 2>&1 &', $command));
    }
}
