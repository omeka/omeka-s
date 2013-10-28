<?php
require('cli-config.php');

use Omeka\Install\Installer;
use Omeka\Service\EntityManagerFactory;
use Zend\Mvc\Service\ServiceManagerConfig;
use Zend\ServiceManager\ServiceManager;

$config = include 'config/application.config.php';
$application = Zend\Mvc\Application::init(require 'config/application.config.php');
$installer = $application->getServiceManager()->get('Installer');
$success = $installer->install();
if($success) {
    echo 'ok';    
} else {
    echo 'fail';   
    $tasks = $installer->getTasks();
    foreach($tasks as $task) {
        $taskResult = $task->getTaskResult();
        echo "\n" . $task->getTaskName() . ': '; 
        if($taskResult->getSuccess()) {
            echo ' OK';
        } else {
            $taskMessages = $taskResult->getMessages();
            foreach($taskMessages as $messages) {
                foreach($messages as $message)
                echo ' ' . $message['code'] . ' ' . $message['message'];
            }
            break;
        }
    }
    
}

