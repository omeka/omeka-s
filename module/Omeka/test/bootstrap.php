<?php
require '../../../bootstrap.php';

use Omeka\Installation\Manager as InstallationManager;

// Initialize the application.
$applicationConfig = require 'config/application.config.php';
$applicationConfig['module_listener_options']['config_glob_paths']
    = array(OMEKA_PATH . '/module/Omeka/test/test.config.php');
$application = Zend\Mvc\Application::init($applicationConfig);

// Drop the schema.
$connection = $application->getServiceManager()->get('EntityManager')->getConnection();
$connection->query('SET FOREIGN_KEY_CHECKS=0');
foreach ($connection->getSchemaManager()->listTableNames() as $table) {
    $connection->executeUpdate($connection->getDatabasePlatform()->getDropTableSQL($table));
}
$connection->query('SET FOREIGN_KEY_CHECKS=1');

// Install the schema.
$manager = new InstallationManager;
$manager->setServiceLocator($application->getServiceManager());
$manager->registerTask('Omeka\Installation\Task\InstallSchemaTask');
$result = $manager->install();
