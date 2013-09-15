<?php
namespace Omeka\Install;

use Omeka\Install\SchemaTask;
use Zend\ServiceManager\ServiceLocatorInterface;

class Install
{
    private $tasks = array();
    public $serviceLocator;
    public $messages = array();
    public $success = true;
    
    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }
    
    public function addTask($taskName)
    {
        $this->tasks[] = $taskName;
    }
    
    public function install()
    {
        $this->checkDb();
        $this->checkFileSystem();
        //without db and filesystem in place, no other install steps will work
        //so return here
        if(!$this->success) {
            return;
        }
        
        foreach($this->tasks as $taskName) {
            $fullTaskName = '\\Omeka\\Install\\' . ucfirst($taskName) . 'Task';
            $task = new $fullTaskName($this->serviceLocator);
            $task->perform();
            if(!$task->getSuccessState()) {
                $this->success = false;
            }
            $this->addMessages($taskName, $task->getMessages());
        }
        return $this->success;
        return array('success'=>$this->success, $this->getMessages());
    }
    
    public function getMessages()
    {
        return $this->messages;
    }
    
    protected function addMessages($taskName, $messages)
    {
        $this->messages[$taskName] = $messages;
    }
    
    /**
     * Check that the database configuration is okay
     */
    protected function checkDb()
    {
        //$config = $this->serviceLocator->get('ApplicationConfig');
        //print_r($config);
        $em = $this->serviceLocator->get('EntityManager');
        $conn = $em->getConnection();
        try {
            $conn->connect();
        } catch(\Exception $e) {
            $this->success = false;
            $this->addMessages('Database check', array(array('message'=>"The database is not correctly configured. Check the settings in application.config.php.", 'code'=>'WTF')));
        }
    }
    
    /**
     * Check that appropriate directories are writable by Omeka
     */
    
    protected function checkFileSystem()
    {
        //$this->addMessages('File system check', array(array('message'=>'file system ok', 'code'=>'OK')));
        //$this->addMessages('File system check', array(array('message'=>'Check the file system', 'code'=>'WTF')));
    }
    
}