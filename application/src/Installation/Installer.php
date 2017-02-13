<?php
namespace Omeka\Installation;

use Omeka\Stdlib\ErrorStore;
use Zend\ServiceManager\ServiceLocatorInterface;

class Installer
{
    /**
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator;

    /**
     * @var array Registered pre-installation tasks.
     */
    protected $preTasks = [];

    /**
     * @var array Registered installation tasks.
     */
    protected $tasks = [];

    /**
     * @var array Error messages
     */
    protected $errors = [];

    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    /**
     * Run pre-installation checks.
     *
     * @return bool Whether the pre-installation checks passed.
     */
    public function preInstall()
    {
        foreach ($this->getPreTasks() as $taskName) {
            try {
                $task = new $taskName;
                $task->perform($this);
            } catch (\Exception $e) {
                $this->addError($e->getMessage());
            }
        }

        return !($this->getErrors());
    }

    /**
     * Install Omeka.
     *
     * @return bool Whether the installation was successful.
     */
    public function install()
    {
        // Even if checked before, run the "pre" checks again before the actual install tasks
        if (!$this->preInstall()) {
            return false;
        }

        foreach ($this->getTasks() as $taskName) {
            try {
                $task = new $taskName;
                $task->perform($this);
            } catch (\Exception $e) {
                $this->addError($e->getMessage());
            }

            // Stop immediately upon any error
            if ($this->getErrors()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Register an installation task.
     *
     * @param string $task
     */
    public function registerTask($task)
    {
        $this->tasks[] = $task;
    }

    /**
     * Register an pre-installation task to occur before the installation begins.
     *
     * @param string $task
     */
    public function registerPreTask($task)
    {
        $this->preTasks[] = $task;
    }

    /**
     * Get registered installation tasks.
     *
     * @return array
     */
    public function getTasks()
    {
        return $this->tasks;
    }

    /**
     * Get registered pre-installation tasks.
     *
     * @return array
     */
    public function getPreTasks()
    {
        return $this->preTasks;
    }

    /**
     * Register a specific task's variables.
     *
     * @param string $task
     * @param array $vars
     */
    public function registerVars($task, array $vars)
    {
        $this->vars[$task] = $vars;
    }

    /**
     * Get a specific task's variables.
     *
     * @return array|null
     */
    public function getVars($task)
    {
        return isset($this->vars[$task]) ? $this->vars[$task] : null;
    }

    /**
     * Add errors derived from an ErrorStore.
     *
     * @param ErrorStore $errorStore
     */
    public function addErrorStore(ErrorStore $errorStore)
    {
        foreach ($errorStore->getErrors() as $error) {
            foreach ($error as $message) {
                $this->addError($message);
            }
        }
    }

    /**
     * Add an error message.
     *
     * @param string $message
     */
    public function addError($message)
    {
        $this->errors[] = $message;
    }

    /**
     * Get all error messages.
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Get the service locator.
     *
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }
}
