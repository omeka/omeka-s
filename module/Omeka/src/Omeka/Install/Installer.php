<?php
namespace Omeka\Install;

use Omeka\Install\Task\TaskInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

/**
 * Installs the Omeka schema and initial data
 * 
 * @author patrickmj
 *
 */

class Installer implements ServiceLocatorAwareInterface
{
    public $messages = array();
    public $success = true;
    protected $tasks = array();
    protected $services;


    /**
     * Add a task to the list of installation tasks
     * 
     * @param TaskInterface $task 
     */
    public function addTask(TaskInterface $task)
    {
        $this->tasks[] = $task;
    }

    /**
     * Load all the tasks from the application config
     */
    public function loadTasks()
    {
        $config = $this->getServiceLocator()->get('Config');
        $tasks = $config['install']['tasks'];
        foreach($tasks as $task) {
            $this->addTask(new $task);
        }
    }
    
    /**
     * Get all the added tasks
     * 
     * @return array
     */
    public function getTasks()
    {
        return $this->tasks;
    }
    
    /**
     * Perform all the installation tasks
     * 
     * @return boolean
     */
    public function install()
    {
        $serviceLocator = $this->getServiceLocator();
        foreach($this->tasks as $task) {
            $task->setServiceLocator($serviceLocator);
            $task->perform();
            $result = $task->getResult();
            $this->addMessages($result->getMessages());
            if(!$result->getSuccess()) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Return a list of messages from installation tasks
     * 
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }
    
    /**
     * Set the service locator
     * 
     * @see Zend\ServiceManager.ServiceLocatorAwareInterface::setServiceLocator()
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->services = $serviceLocator;
    }

    /**
     * Get the service locator
     * 
     * @see Zend\ServiceManager.ServiceLocatorAwareInterface::getServiceLocator()
     */
    public function getServiceLocator()
    {
        return $this->services;
    }
    
    /**
     * Add messages from an installation task
     * 
     * @param array $messages
     */
    protected function addMessages($messages)
    {
        $this->messages[] = $messages;
    }    
}