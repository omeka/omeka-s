<?php
ini_set('display_errors', true);

require 'init_autoloader.php';

$application = Zend\Mvc\Application::init(require 'config/application.config.php');
$em = $application->getServiceManager()->get('EntityManager');
