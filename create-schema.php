<?php
/**
 * Create optimized schema SQL for installation.
 *
 * Doctrine's schema-tool creates unoptimized schema SQL that takes an
 * inordinate time to install. Thankfully, mysqldump creates more highly
 * optimized SQL. That, along with toggling off foreign key checks, greatly
 * reduces installation time.
 */

require 'bootstrap.php';

$config = require OMEKA_PATH . '/config/application.config.php';
$reader = new \Zend\Config\Reader\Ini;
$testConfig = array(
    'connection' => $reader->fromFile(OMEKA_PATH . '/application/test/config/database.ini')
);
$config = array_merge($config, $testConfig);

// Initialize the Omeka application using the test database.
$application = Omeka\Mvc\Application::init($config);
$entityManager = $application->getServiceManager()->get('Omeka\EntityManager');
$schemaTool = new \Doctrine\ORM\Tools\SchemaTool($entityManager);
$metadata = $entityManager->getMetadataFactory()->getAllMetadata();

// Drop existing tables and create the schema.
$schemaTool->dropSchema($metadata);
$schemaTool->createSchema($metadata);

// Build the schema SQL.
$user = escapeshellarg($testConfig['connection']['user']);
$password = escapeshellarg($testConfig['connection']['password']);
$dbname = escapeshellarg($testConfig['connection']['dbname']);

$schemaSql = 'SET FOREIGN_KEY_CHECKS = 0;' . PHP_EOL;
$schemaSql .= shell_exec("mysqldump --compact --user $user --password=$password $dbname");
$schemaSql .= 'SET FOREIGN_KEY_CHECKS = 1;' . PHP_EOL;
$schemaSql = preg_replace('/\/\*.+\*\/;\n/', '', $schemaSql);
file_put_contents('data/install/schema.sql', $schemaSql);

// Clean up.
$schemaTool->dropSchema($metadata);
