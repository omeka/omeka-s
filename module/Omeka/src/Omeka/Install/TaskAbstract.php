<?php
namespace Omeka\Install;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

abstract class TaskAbstract implements ServiceLocatorAwareInterface, TaskInterface
{
    protected $services;
    protected $taskName;
    protected $result;
    
    public function __construct()
    {
        $this->result = new TaskResult($this);  
    }   
     
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->services = $serviceLocator;
    }
    
    public function getServiceLocator()
    {
        return $this->services;
    }    
    
    public function getTaskName()
    {
        return $this->taskName;
    }
    
    public function getResult()
    {
        return $this->result;
    }
}