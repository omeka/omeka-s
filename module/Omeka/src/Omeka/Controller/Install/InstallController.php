<?php
namespace Omeka\Controller\Install;

use Omeka\Install\Installer;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\View\Model\ViewModel;

class InstallController extends AbstractActionController
{
    protected $installer;
    protected $services;
    
    public function indexAction()
    {
        $messages = array();
        $success = true;
        if(isset($_POST['submit'])) {
            $installer = new Installer;
            $installer->setServiceLocator($this->getServiceLocator());
            $installer->loadTasks();
            $success = $installer->install();
            $messages = $installer->getMessages();
        }
        return new ViewModel(array('messages'=>$messages, 'success'=>$success));
    }
    
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->services = $serviceLocator;
    }
    
    public function getServiceLocator()
    {
        return $this->services;
    }    
}