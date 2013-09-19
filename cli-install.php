<?php
require('cli-config.php');

use Omeka\Install\Installer;
use Omeka\Service\EntityManagerFactory;
use Zend\Mvc\Service\ServiceManagerConfig;
use Zend\ServiceManager\ServiceManager;

$factory = new EntityManagerFactory;
$config = include 'config/application.config.php';
$em = $factory->createEntityManager($config['entity_manager']);

$serviceManager = new ServiceManager(new ServiceManagerConfig());
$serviceManager->setService('ApplicationConfig', $config);
$serviceManager->setService('EntityManager', $em);
$serviceManager->get('ModuleManager')->loadModules();

$installer = new Installer;
$installer->setServiceLocator($serviceManager);
$installer->loadTasks();
$success = $installer->install();
if($success) {
    echo 'ok';    
} else {
    echo 'fail';   
    print_r($installer->getMessages());
}

