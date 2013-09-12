<?php

namespace Omeka\Install;

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
            $fullTaskName = $taskName . 'Task';
            $task = new $fullTaskName();
            $task->perform();
        }
    }
}