<?php
namespace Omeka\Controller;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

abstract class AbstractRestfulController extends \Zend\Mvc\Controller\AbstractRestfulController 
    implements ServiceLocatorAwareInterface
{
    protected $services;

    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->services = $serviceLocator;
    }
    
    public function getServiceLocator()
    {
        return $this->services;
    }
}
