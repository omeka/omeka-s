<?php
namespace Omeka\Test;

use Omeka\Installation\Manager as InstallationManager;
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
        self::getApplication()->getServiceManager()->get('Omeka\qEntityManager')
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
        $config = require OMEKA_PATH . '/config/application.config.php';
        $reader = new \Zend\Config\Reader\Ini;
        $testConfig = array(
            'connection' => $reader->fromFile(OMEKA_PATH . '/application/Omeka/test/config/database.ini')
        );
        $config = array_merge($config, $testConfig);
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
        $manager = new InstallationManager;
        $manager->setServiceLocator(self::getApplication()->getServiceManager());
        $manager->registerTask('Omeka\Installation\Task\InstallSchemaTask');
        $result = $manager->install();
    }
}
