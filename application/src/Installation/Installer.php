<?php
namespace Omeka\Installation;

use Omeka\Stdlib\ErrorStore;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class Installer implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * @var array Registered installation tasks.
     */
    protected $tasks;

    /**
     * @var array Error messages
     */
    protected $errors = [];

    /**
     * Install Omeka.
     *
     * @return bool Whether the installation was successful.
     */
    public function install()
    {
        foreach ($this->getTasks() as $taskName) {

            try {
                $task = new $taskName;
                $task->perform($this);
            } catch (\Exception $e) {
                $this->addError($e->getMessage());
            }

            // Tasks are dependent on previously run tasks. If there is an
            // error, stop installation immediately and return false.
            if ($this->getErrors()) {
                return false;
            }
        }

        // Installation successful.
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
     * Get registered installation tasks.
     * 
     * @return array
     */
    public function getTasks()
    {
        return $this->tasks;
    }

    /**
     * Register a specific task's variables.
     *
     * @param str $task
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
}
