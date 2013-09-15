<?php
namespace Omeka\Install;

use Zend\ServiceManager\ServiceLocatorInterface;

abstract class InstallTaskAbstract
{
    public $serviceLocator;
    public $messages = array();
    public $success = true;
    
    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }
    
    public function addMessage($message, $code = 'INFO')
    {
        $this->messages[] = array('code'=>$code, 'message'=>$message);
    }
    
    public function getMessages()
    {
        return $this->messages;
    }
    
    public function setFail()
    {
        $this->success = false;
    }
    
    public function getSuccessState()
    {
        return $this->success;
    }
}