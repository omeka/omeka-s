<?php
namespace OmekaTest;

use Omeka\Service\EntityManagerFactory;
use Zend\Loader\AutoloaderFactory;
use Zend\Mvc\Service\ServiceManagerConfig;
use Zend\ServiceManager\ServiceManager;
use Omeka\Install\Installer;
use RuntimeException;

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);
chdir(__DIR__);

/**
 * Test bootstrap, for setting up autoloading
 */
class Bootstrap
{
    protected static $serviceManager;
    protected static $entityManagerConfig;
    protected static $applicationConfig;
    protected static $entityManager;

    public static function init()
    {
        $omekaModulePaths = array(dirname(dirname(__DIR__)));
        if (($path = static::findParentPath('vendor'))) {
            $omekaModulePaths[] = $path;
        }
        if (($path = static::findParentPath('module')) !== $omekaModulePaths[0]) {
            $omekaModulePaths[] = $path;
        }
        
        static::initAutoloader();

        static::$applicationConfig = include './../../../config/application.config.php';
        
        static::$entityManagerConfig = include('./test.config.php');
        
        self::$applicationConfig['module_listener_options'] = array(
                        'module_paths' => $omekaModulePaths,
                );

        $factory = new EntityManagerFactory;
        $em = $factory->createEntityManager(self::$entityManagerConfig);                
        $serviceManager = new ServiceManager(new ServiceManagerConfig());
        $serviceManager->setService('ApplicationConfig', self::$applicationConfig);
        $serviceManager->setService('EntityManager', $em);        
        $serviceManager->get('ModuleManager')->loadModules();
        static::$serviceManager = $serviceManager;
        static::$entityManager = $em;
    }
    
    public static function installTables()
    {
        $installer = new Installer();
        $installer->setServiceLocator(self::$serviceManager);        
        $installer->addTask(new \Omeka\Install\TaskConnectDb);
        $installer->addTask(new \Omeka\Install\TaskSchema);
        $installer->install();
    }
    
    public static function dropTables()
    {
        $factory = new EntityManagerFactory;
        $em = $factory->createEntityManager(Bootstrap::getEntityManagerConfig());        
        $connection = $em->getConnection();
        $tables   = $connection->getSchemaManager()->listTableNames();
        $platform   = $connection->getDatabasePlatform();
        $connection->query('SET FOREIGN_KEY_CHECKS=0');
        foreach($tables as $table) {
            $connection->executeUpdate($platform->getDropTableSQL($table));
        }
        $connection->query('SET FOREIGN_KEY_CHECKS=1');    
    }

    public static function chroot()
    {
        $rootPath = dirname(static::findParentPath('module'));
        chdir($rootPath);
    }

    public static function getServiceManager()
    {
        return static::$serviceManager;
    }
    
    public static function getApplicationConfig()
    {
        return static::$applicationConfig;
    }
    
    public static function getEntityManagerConfig()
    {
        return static::$entityManagerConfig;
    }
    
    public static function getEntityManager()
    {
        return static::$entityManager;
    }

    protected static function initAutoloader()
    {
        $vendorPath = static::findParentPath('vendor');        
        require $vendorPath . '/autoload.php';
    }

    protected static function findParentPath($path)
    {
        $dir = __DIR__;
        $previousDir = '.';
        while (!is_dir($dir . '/' . $path)) {
            $dir = dirname($dir);
            if ($previousDir === $dir) return false;
            $previousDir = $dir;
        }
        return $dir . '/' . $path;
    }
}

Bootstrap::init();
Bootstrap::chroot();