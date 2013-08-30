<?php
namespace OmekaTest;

require_once '/var/www/Omeka3/module/Omeka/src/Omeka/Test/ModelTest.php';

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
        
        static::$entityManagerConfig = include('./testdb.config.php');
        
        
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
        $zf2Path = getenv('ZF2_PATH');
        if (!$zf2Path) {
            if (defined('ZF2_PATH')) {
                $zf2Path = ZF2_PATH;
            } elseif (is_dir($vendorPath . '/ZF2/library')) {
                $zf2Path = $vendorPath . '/ZF2/library';
            } elseif (is_dir($vendorPath . '/zendframework/zendframework/library')) {
                $zf2Path = $vendorPath . '/zendframework/zendframework/library';
            }
        }

        if (!$zf2Path) {
            throw new RuntimeException(
                    'Unable to load ZF2. Run `php composer.phar install` or'
                    . ' define a ZF2_PATH environment variable.'
            );
        }

        if (file_exists($vendorPath . '/autoload.php')) {
            include $vendorPath . '/autoload.php';
        }

        include $zf2Path . '/Zend/Loader/AutoloaderFactory.php';
        AutoloaderFactory::factory(array(
                'Zend\Loader\StandardAutoloader' => array(
                        'autoregister_zf' => true,
                        'namespaces' => array(
                                __NAMESPACE__ => __DIR__ . '/' . __NAMESPACE__,
                        ),
                ),
        ));
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