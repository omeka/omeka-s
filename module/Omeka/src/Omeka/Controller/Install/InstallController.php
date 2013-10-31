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
            $installer = $this->getServiceLocator()->get('Installer');
            $success = $installer->install();
            $tasks = $installer->getTasks();
            foreach($tasks as $task) {
                $taskResult = $task->getTaskResult();
                if($taskResult->getSuccess()) {
                    $messages[] = array('taskName' => $task->getTaskName(), 
                                        'messages' => array(array('code' => 'OK', 'message' => 'Completed'))
                                       );
                } else {
                    $messages[] = $taskResult->getMessages();
                    break;
                }
            }
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