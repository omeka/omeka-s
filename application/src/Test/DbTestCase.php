<?php
namespace Omeka\Test;

use Zend\Mvc\Application;

/**
 * Database test case.
 *
 * Tests that need access to Doctrine's entity manager (e.g. for writing to and
 * querying the database) should extend off this class. For every test, this
 * starts a transaction during setUp, and rolls back any changes to the database
 * during tearDown. A fresh database should have been installed when
 * bootstrapping PHPUnit.
 */
class DbTestCase extends TestCase
{
    /**
     * The test application.
     *
     * @var Application
     */
    protected static $application;

    /**
     * Set the test application and begin a transaction during setUp.
     * Child classes should call parent::setUp() in their own setUp.
     */
    public function setUp()
    {
        self::getApplication()->getServiceManager()->get('Omeka\EntityManager')
            ->getConnection()->beginTransaction();
    }

    /**
     * Rollback the transaction during tear down.
     */
    public function tearDown()
    {
        self::getApplication()->getServiceManager()->get('Omeka\EntityManager')
            ->getConnection()->rollback();
    }

    /**
     * Get the test application.
     *
     * @return Application
     */
    public static function getApplication()
    {
        // Return the application immediately if already set.
        if (self::$application instanceof Application) {
            return self::$application;
        }
        $config = require OMEKA_PATH . '/application/config/application.config.php';
        $reader = new \Zend\Config\Reader\Ini;
        $testConfig = [
            'connection' => $reader->fromFile(OMEKA_PATH . '/application/test/config/database.ini'),
        ];
        $config = array_merge($config, $testConfig);
        \Zend\Console\Console::overrideIsConsole(false);
        self::$application = Application::init($config);
        return self::$application;
    }

    /**
     * Drop the test database schema.
     */
    public static function dropSchema()
    {
        $connection = self::getApplication()->getServiceManager()
            ->get('Omeka\EntityManager')->getConnection();
        $connection->query('SET FOREIGN_KEY_CHECKS=0');
        foreach ($connection->getSchemaManager()->listTableNames() as $table) {
            $connection->executeUpdate(
                $connection->getDatabasePlatform()
                    ->getDropTableSQL($table)
            );
        }
        $connection->query('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Install the test database schema.
     */
    public static function installSchema()
    {
        $application = self::getApplication();
        $serviceLocator = $application->getServiceManager();

        $status = $serviceLocator->get('Omeka\Status');
        if (!$status->isInstalled()) {
            // Without this, at some point during install the view helper Url
            // will throw an exception 'Request URI has not been set'
            $router = $serviceLocator->get('Router');
            $router->setRequestUri(new \Zend\Uri\Http('http://example.com'));

            $installer = $serviceLocator->get('Omeka\Installer');
            $installer->registerVars(
                'Omeka\Installation\Task\CreateFirstUserTask', [
                    'name' => 'Admin',
                    'email' => 'admin@example.com',
                    'password' => 'root',
                ]);
            $installer->registerVars(
                'Omeka\Installation\Task\AddDefaultSettingsTask', [
                    'administrator_email' => 'admin@example.com',
                    'installation_title' => 'Omeka S Test',
                    'time_zone' => 'UTC',
                    'locale' => 'en_US',
                ]);

            if (!$installer->install()) {
                file_put_contents('php://stdout', "Error(s) installing:\n");
                foreach ($installer->getErrors() as $error) {
                    file_put_contents('php://stdout', "\t$error\n");
                }
                exit;
            }
        }
    }
}
