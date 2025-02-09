<?php
/**
 * Create optimized schema SQL for installation.
 *
 * Doctrine's schema-tool creates unoptimized schema SQL that takes an
 * inordinate time to install. Thankfully, mysqldump creates more highly
 * optimized SQL. That, along with toggling off foreign key checks, greatly
 * reduces installation time.
 */
require dirname(dirname(dirname(__DIR__))) . '/bootstrap.php';

// Initialize the Omeka application using the test database.
$config = require OMEKA_PATH . '/application/config/application.config.php';
$testConfig = ['connection' => parse_ini_file(
    OMEKA_PATH . '/application/test/config/database.ini'
)];
$application = Omeka\Mvc\Application::init(array_merge($config, $testConfig));
$entityManager = $application->getServiceManager()->get('Omeka\EntityManager');

// Create new tables.
dropTables($entityManager->getConnection());
$schemaTool = new Doctrine\ORM\Tools\SchemaTool($entityManager);
$schemaTool->createSchema($entityManager->getMetadataFactory()->getAllMetadata());

// Build the schema SQL.
$user = escapeshellarg($testConfig['connection']['user']);
$password = escapeshellarg($testConfig['connection']['password']);
$dbname = escapeshellarg($testConfig['connection']['dbname']);
$schemaSql = 'SET FOREIGN_KEY_CHECKS = 0;' . PHP_EOL;
$schemaSql .= shell_exec("mysqldump --compact --user $user --password=$password $dbname");
$schemaSql .= 'SET FOREIGN_KEY_CHECKS = 1;' . PHP_EOL;
$schemaSql = preg_replace('/\/\*.+\*\/;\n/', '', $schemaSql);
file_put_contents('application/data/install/schema.sql', $schemaSql);

// Clean up.
dropTables($entityManager->getConnection());

/**
 * Drop all existing tables, even those not defined in the schema.
 *
 * @param Doctrine\DBAL\Connection $connection
 */
function dropTables(Doctrine\DBAL\Connection $connection)
{
    $connection->query('SET FOREIGN_KEY_CHECKS=0');
    foreach ($connection->getSchemaManager()->listTableNames() as $table) {
        $connection->getSchemaManager()->dropTable($table);
    }
    $connection->query('SET FOREIGN_KEY_CHECKS=1');
}
