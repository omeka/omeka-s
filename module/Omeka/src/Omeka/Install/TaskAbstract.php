<?php
namespace Omeka\Install;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

abstract class TaskAbstract implements ServiceLocatorAwareInterface, TaskInterface
{
    protected $services;
    protected $taskName;
    protected $result;
    protected $installDataPath;
    
    public function __construct()
    {
        $this->result = new TaskResult($this);
        $this->installDataPath = $this->findInstallDataPath();  
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
    
    /**
     * Find the correct path to the install data, regardless of whether running through
     * browser or through cli.
     */
    protected function findInstallDataPath()
    {
        $dir = __DIR__;
        $previousDir = '.';
        while (!is_dir($dir . '/install_data')) {
            $dir = dirname($dir);
            if ($previousDir === $dir) return false;
            $previousDir = $dir;
        }
        return $dir . '/install_data';
    }    
    
}