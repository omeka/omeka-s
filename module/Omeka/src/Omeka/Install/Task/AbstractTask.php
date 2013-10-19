<?php
namespace Omeka\Install\Task;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Omeka\Install\Exception\TaskSetupException;

/**
 * Abstraction of installation tasks
 * 
 * Each installation task must be listed in application.config.php
 * @author patrickmj
 *
 */
abstract class AbstractTask implements ServiceLocatorAwareInterface, TaskInterface
{
    protected $services;
    protected $taskName;
    protected $result;
    protected $installDataPath;
    
    public function __construct()
    {
        $this->result = new TaskResult($this);
        $this->installDataPath = $this->findInstallDataPath();
        if(!$this->taskName) {
            throw new TaskSetupException("taskName must be set for install tasks");
        }  
    }   
    
    /**
     * Set the service locator
     * 
     * @param Zend\ServiceLocatorInterface
     * @see Zend\ServiceManager.ServiceLocatorAwareInterface::setServiceLocator()
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->services = $serviceLocator;
    }
    
    /**
     * Get the service locator
     * 
     * @return ServiceLocatorInterface
     * @see Zend\ServiceManager.ServiceLocatorAwareInterface::getServiceLocator()
     */
    public function getServiceLocator()
    {
        return $this->services;
    }    
    
    /**
     * Get the task name. 
     * 
     * This is used in display user-friendly messages for each installation task
     * @return string
     */
    public function getTaskName()
    {
        return $this->taskName;
    }
    
    /**
     * Return the result object for the task
     * 
     * @return TaskResult
     */
    public function getTaskResult()
    {
        return $this->result;
    }
    
    /**
     * Find the correct path to the install data, regardless of whether running through
     * browser or through cli.
     */
    protected function findInstallDataPath()
    {
        $dir = __DIR__;
        $previousDir = '.';
        while (!is_dir($dir . '/data')) {
            $dir = dirname($dir);
            if ($previousDir === $dir) return false;
            $previousDir = $dir;
        }
        return $dir . '/data/install';
    }    
    
}