<?php
namespace Omeka\Installation;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Installation manager service.
 */
class Manager implements ServiceLocatorAwareInterface
{
    /**
     * @var ServiceLocatorInterface
     */
    protected $services;

    /**
     * @var array Registered installation tasks.
     */
    protected $tasks = array();

    /**
     * Install Omeka.
     *
     * @return Result
     */
    public function install()
    {
        $result = new Result;
        foreach ($this->getTasks() as $task) {
            $task = new $task;
            if ($task instanceof ServiceLocatorAwareInterface) {
                $task->setServiceLocator($this->getServiceLocator());
            }
            $result->setCurrentTask(get_class($task), $task->getName());
            $task->perform($result);
            // Tasks tend to be dependent on previously run tasks. If there is
            // an error, stop installation immediately and return the result.
            if ($result->isError()) {
                return $result;
            }
        }
        return $result;
    }

    /**
     * Register an installation task.
     * 
     * @param string $task
     */
    public function registerTask($task)
    {
        if (!class_exists($task)) {
            throw new Exception\ConfigException(sprintf(
                'The "%s" installation task does not exist.', 
                $task
            ));
        }
        if (!in_array('Omeka\Installation\Task\TaskInterface', class_implements($task))) {
            throw new Exception\ConfigException(sprintf(
                'The "%s" installation task does not implement Omeka\Installation\Task\TaskInterface.', 
                $task
            ));
        }
        $this->tasks[] = $task;
    }

    /**
     * Register installation tasks.
     * 
     * @param array $tasks
     */
    public function registerTasks(array $tasks)
    {
        foreach ($tasks as $task) {
            $this->registerTask($task);
        }
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
     * {@inheritdoc}
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->services = $serviceLocator;
    }

    /**
     * {@inheritdoc}
     */
    public function getServiceLocator()
    {
        return $this->services;
    }
}
