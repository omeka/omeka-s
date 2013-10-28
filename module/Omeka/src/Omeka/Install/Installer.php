<?php
namespace Omeka\Install;

use Omeka\Install\Task\TaskInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

/**
 * Installs the Omeka schema and initial data
 * 
 * Initial schema in /data/install/schema.sql is generated via
 * php vendor/bin/doctrine orm:schema-tool:create --dump-sql > data/install/schema.sql
 * To handle prefixes, the dump MUST have dbprefix DBPREFIX_
 * 
 */
class Installer implements ServiceLocatorAwareInterface
{
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
     * Get all the added tasks
     * 
     * @return array
     */
    public function getTasks()
    {
        return $this->tasks;
    }
    
    /**
     * Empty the list of tasks. Used for testing
     */
    public function emptyTasks()
    {
        $this->tasks = array();
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
            $result = $task->getTaskResult();
            //$this->addResult($result);
            if(!$result->getSuccess()) {
                return false;
            }
        }
        return true;
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
}