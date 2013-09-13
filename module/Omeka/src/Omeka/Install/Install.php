<?php
namespace Omeka\Install;

use Omeka\Install\SchemaTask;

class Install
{
    private $tasks = array();
    
    public function addTask($taskName)
    {
        $this->tasks[] = $taskName;
    }
    
    public function install()
    {
        foreach($this->tasks as $taskName) {
            //$task = new SchemaTask;
            $fullTaskName = '\\Omeka\\Install\\' . ucfirst($taskName) . 'Task';
            $task = new $fullTaskName;
            $task->perform();
        }
    }
}