<?php
namespace OmekaTest;

use Omeka\Service\EntityManagerFactory;
use Zend\Loader\AutoloaderFactory;
use Zend\Mvc\Service\ServiceManagerConfig;
use Zend\ServiceManager\ServiceManager;
use RuntimeException;

error_reporting(E_ALL | E_STRICT);
chdir(__DIR__);

/**
 * Test bootstrap, for setting up autoloading
 */
class Bootstrap
{
    protected static $serviceManager;
    protected static $entityManagerConfig;
    protected static $applicationConfig;

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
        
        
        // use ModuleManager to load this module and it's dependencies
        $config = array(
                'module_listener_options' => array(
                        'module_paths' => $omekaModulePaths,
                ),
                'modules' => array(
                        'Omeka'
                )
        );

        $serviceManager = new ServiceManager(new ServiceManagerConfig());
        $serviceManager->setService('ApplicationConfig', $config);
        $serviceManager->get('ModuleManager')->loadModules();
        static::$serviceManager = $serviceManager;
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