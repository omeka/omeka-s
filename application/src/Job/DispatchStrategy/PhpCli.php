<?php
namespace Omeka\Job\DispatchStrategy;

use Omeka\Job\Exception;
use Omeka\Entity\Job;
use Omeka\Stdlib\Cli;

class PhpCli implements StrategyInterface
{
    /**
     * @var Cli
     */
    protected $cli;

    /**
     * @var string
     */
    protected $basePath;

    /**
     * @var string|null
     */
    protected $phpPath;

    /**
     * Create the PHP-CLI-based job dispatch strategy.
     *
     * @param Cli $cli CLI service
     * @param string $basePath Base URL for the installation
     * @param string|null $phpPath Path to the PHP CLI
     */
    public function __construct(Cli $cli, $basePath, $phpPath = null)
    {
        $this->cli = $cli;
        $this->basePath = $basePath;
        $this->phpPath = $phpPath;
    }

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
        if ($this->phpPath) {
            $phpPath = $this->cli->validateCommand($this->phpPath);
            if (false === $phpPath) {
                throw new Exception\RuntimeException('PHP-CLI error: invalid PHP path.');
            }
        } else {
            $phpPath = $this->cli->getCommandPath('php');
            if (false === $phpPath) {
                throw new Exception\RuntimeException('PHP-CLI error: cannot determine path to PHP.');
            }
        }

        $script = OMEKA_PATH . '/application/data/scripts/perform-job.php';

        $command = sprintf(
            '%s %s --job-id %s --base-path %s',
            escapeshellcmd($phpPath),
            escapeshellarg($script),
            escapeshellarg($job->getId()),
            escapeshellarg($this->basePath)
        );

        $status = $this->cli->execute(sprintf('%s > /dev/null 2>&1 &', $command));
        if ($status === false) {
            throw new Exception\RuntimeException('PHP-CLI error: job script failed to execute.');
        }
    }
}
