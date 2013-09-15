<?php
namespace Omeka\Controller\Install;

use Omeka\Install\Install;
use Omeka\Controller\AbstractRestfulController;
use Zend\View\Model\ViewModel;

class InstallController extends AbstractRestfulController
{
    protected $installer;
    
    public function indexAction()
    {
        //give the installer the service locator, which gets passed on
        //to each install task, so the tasks can dig up whatever data they need,
        //usually the Entity Manager
        
        $this->installer = new Install($this->getServiceLocator()); 
        $this->install();
        
        //stuff the messages into the view somehow
        return new ViewModel(array('messages'=>$this->installer->getMessages(), 'success'=>$this->installer->success));
    }
    
    protected function install()
    {

        //Each task name for addTask must have a corresponding class
        //that extends InstallTaskAbstract and implements InstallTaskInterface.
        //Naming convention is the class name is the name here, plus "Task", CamelCased,
        //e.g. class SchemaTask for 'schema'. Namespace is Omeka\Install
        
        $this->installer->addTask('schema');
        $this->installer->install();
        //return array('success'=>$success, 'messages'=>$installer->getMessages());
    }
}