<?php
namespace Omeka\Service;

use Omeka\Module\Manager as ModuleManager;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Factory for creating Omeka's module manager
 */
class ModuleManagerFactory implements FactoryInterface
{
    /**
     * Create the module manager
     * 
     * @param ServiceLocatorInterface $serviceLocator
     * @return array
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        if (!$serviceLocator->get('Omeka\InstallationManager')->isInstalled()) {
            return array();
        }

        $modules = new ModuleManager;
        $appConfig = $serviceLocator->get('ApplicationConfig');
        $connection = $serviceLocator->get('Omeka\Connection');

        // Get all modules from the filesystem.
        $iniReader = new \Zend\Config\Reader\Ini;
        foreach (new \DirectoryIterator(OMEKA_PATH . '/module') as $fileinfo) {
            if ($fileinfo->isDir()
                && !$fileinfo->isDot()
                && !in_array($fileinfo->getBasename(), $appConfig['modules'])
            ) {
                // Found a non-default module.
                $iniFile = new \SplFileInfo($fileinfo->getPathname() . '/config/module.ini');
                if ($iniFile->isReadable() && $iniFile->isFile()) {
                    // Found a module with config/module.ini in it.
                    $moduleInfo = $iniReader->fromFile($iniFile->getRealPath());

                    // @todo This is where we need validate the plugin's ini
                    // configuration and either set an INVALID_INI state or
                    // just skip over the plugin.

                    $modules->setFound($fileinfo->getBasename(), $moduleInfo);
                }
            }
        }

        // Get all modules from the database.
        $table = $appConfig['connection']['table_prefix'] . 'module';
        $statement = $connection->prepare("SELECT * FROM $table");
        $statement->execute();
        foreach ($statement->fetchAll() as $module) {
            if ($modules->isFound($module['id'])) {

                // @todo This is where we need to compare filesystem version
                // with database version and set an INSTALLED_NEEDS_UPDATE
                // state instead of ACTIVE or NOT_ACTIVE.

                if ($module['is_active']) {
                    // Module found, installed, and active
                    $modules->setToState($module['id'], ModuleManager::STATE_ACTIVE);
                } else {
                    // Module found, installed, and not active
                    $modules->setToState($module['id'], ModuleManager::STATE_NOT_ACTIVE);
                }
            } else {
                // Module found in the database but not in the filesystem
                $modules->setToState($module['id'], ModuleManager::STATE_NOT_FOUND);
            }
        }

        foreach ($modules->getFound() as $id => $info) {
            if (!$modules->isInState($id)) {
                // Module found but not installed
                $modules->setToState($id, ModuleManager::STATE_NOT_INSTALLED);
            }
        }

        return $modules;
    }
}
