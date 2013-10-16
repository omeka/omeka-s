<?php
namespace Omeka\Install;

use Omeka\Install\SchemaTask;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

class Installer implements ServiceLocatorAwareInterface
{
    public $messages = array();
    public $success = true;
    protected $tasks = array();
    protected $services;

    public function addTask(TaskInterface $task)
    {
        $this->tasks[] = $task;
    }
    
    public function loadTasks()
    {
        $config = $this->getServiceLocator()->get('ApplicationConfig');
        $tasks = $config['install_tasks'];
        foreach($tasks as $task) {
            $this->addTask(new $task);
        }
    }
    
    public function getTasks()
    {
        return $this->tasks;
    }
    
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
    
    public function getMessages()
    {
        return $this->messages;
    }

    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->services = $serviceLocator;
    }
    
    public function getServiceLocator()
    {
        return $this->services;
    }
    
    protected function addMessages($messages)
    {
        $this->messages[] = $messages;
    }    
}