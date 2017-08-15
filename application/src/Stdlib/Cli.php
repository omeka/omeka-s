<?php
namespace Omeka\Stdlib;

use Zend\Log\LoggerInterface;

class Cli
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var string
     */
    protected $executeStrategy;

    /**
     * @param LoggerInterface $logger
     * @param string $executeStrategy
     */
    public function __construct(LoggerInterface $logger, $executeStrategy)
    {
        $this->logger = $logger;
        $this->executeStrategy = $executeStrategy;
    }

    /**
     * Get a command path.
     *
     * Returns the path to the provided command or boolean false if the command
     * is not found.
     *
     * @param string $command
     * @return string|false
     */
    public function getCommandPath($command)
    {
        $command = sprintf('command -v %s', escapeshellarg($command));
        return $this->execute($command);
    }

    /**
     * Verfy that a command exists and is executable.
     *
     * @param string $commandDir The command's directory or the command path if
     *     $command is not passed
     * @param string $command
     * @return string|false The command path if valid, false otherwise
     */
    public function validateCommand($commandDir, $command = null)
    {
        $commandDir = realpath($commandDir);
        if (false === $commandDir) {
            return false;
        }
        if (null === $command) {
            $commandPath = $commandDir;
        } else {
            if (!@is_dir($commandDir)) {
                return false;
            }
            $commandPath = sprintf('%s/%s', $commandDir, $command);
        }
        if (!@is_file($commandPath) || !@is_executable($commandPath)) {
            return false;
        }
        return $commandPath;
    }

    /**
     * Execute a command.
     *
     * Expects arguments to be properly escaped.
     *
     * @param string $command An executable command
     * @return string|false The command's standard output or false on error
     */
    public function execute($command)
    {
        switch ($this->executeStrategy) {
            case 'proc_open':
                $output = $this->procOpen($command);
                break;
            case 'exec':
            default:
                $output = $this->exec($command);
                break;
        }

        return $output;
    }

    /**
     * Execute command using PHP's exec function.
     *
     * @link http://php.net/manual/en/function.exec.php
     * @param string $command
     * @return string|false
     */
    public function exec($command)
    {
        exec($command, $output, $exitCode);
        if (0 !== $exitCode) {
            $this->logger->err(sprintf('Command "%s" failed with status code %s.', $command, $exitCode));
            return false;
        }
        return implode(PHP_EOL, $output);
    }

    /**
     * Execute command using PHP's proc_open function.
     *
     * For servers that allow proc_open. Logs standard error.
     *
     * @link http://php.net/manual/en/function.proc-open.php
     * @param string $command
     * @return string|false
     */
    public function procOpen($command)
    {
        $descriptorSpec = [
            0 => ['pipe', 'r'], // STDIN
            1 => ['pipe', 'w'], // STDOUT
            2 => ['pipe', 'w'], // STDERR
        ];

        $proc = proc_open($command, $descriptorSpec, $pipes, getcwd());
        if (!is_resource($proc)) {
            return false;
        }

        $input = stream_get_contents($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        foreach ($pipes as $pipe) {
            fclose($pipe);
        }

        $exitCode = proc_close($proc);
        if (0 !== $exitCode) {
            // Log standard error
            $this->logger->err($errors);
            $this->logger->err(sprintf('Command "%s" failed with status code %s.', $command, $exitCode));
            return false;
        }
        return trim($output);
    }
}
