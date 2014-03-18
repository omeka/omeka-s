<?php
namespace Omeka\Service;

use DirectoryIterator;
use Omeka\Module\Manager as ModuleManager;
use SplFileInfo;
use Zend\Config\Reader\Ini as IniReader;
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

        $modules    = new ModuleManager;
        $iniReader  = new IniReader;
        $appConfig  = $serviceLocator->get('ApplicationConfig');
        $connection = $serviceLocator->get('Omeka\Connection');

        // Get all modules from the filesystem.
        foreach (new DirectoryIterator(OMEKA_PATH . '/module') as $dir) {

            // Module must be a directory
            if (!$dir->isDir() || $dir->isDot()) {
                continue;
            }

            $modules->setModule($dir->getBasename());

            // Module directory must contain config/module.ini
            $iniFile = new SplFileInfo($dir->getPathname() . '/config/module.ini');
            if (!$iniFile->isReadable() || !$iniFile->isFile()) {
                $modules->setModuleState($dir->getBasename(), ModuleManager::STATE_INVALID_INI);
                continue;
            }

            $moduleIni = $iniReader->fromFile($iniFile->getRealPath());
            $modules->setModuleIni($dir->getBasename(), $moduleIni);

            // Module INI must be valid
            if (!$modules->moduleIniIsValid($moduleIni)) {
                $modules->setModuleState($dir->getBasename(), ModuleManager::STATE_INVALID_INI);
                continue;
            }

            // Module directory must contain Module.php
            $moduleFile = new SplFileInfo($dir->getPathname() . '/Module.php');
            if (!$moduleFile->isReadable() || !$moduleFile->isFile()) {
                $modules->setModuleState($dir->getBasename(), ModuleManager::STATE_INVALID_MODULE);
                continue;
            }

            // Module class must extend Omeka\Module\AbstractModule
            require_once $moduleFile->getRealPath();
            $moduleClass = $dir->getBasename() . '\Module';
            if (!class_exists($moduleClass)
                || !is_subclass_of($moduleClass, 'Omeka\Module\AbstractModule')
            ) {
                $modules->setModuleState($dir->getBasename(), ModuleManager::STATE_INVALID_MODULE);
                continue;
            }
        }

        // Get all modules from the database.
        $table = $appConfig['connection']['table_prefix'] . 'module';
        $statement = $connection->prepare("SELECT * FROM $table");
        $statement->execute();
        foreach ($statement->fetchAll() as $module) {

            if (!$modules->moduleExists($module['id'])) {
                // Module installed but not in filesystem
                $modules->setModule($module['id']);
                $modules->setModuleDb($module['id'], $module);
                $modules->setModuleState($module['id'], ModuleManager::STATE_NOT_FOUND);
                continue;
            }

            $modules->setModuleDb($module['id'], $module);

            if ($modules->moduleHasState($module['id'])) {
                continue;
            }

            // @todo This is where we need to compare filesystem version with
            // database version and set an INSTALLED_NEEDS_UPGRADE state

            if ($module['is_active']) {
                // Module valid, installed, and active
                $modules->setModuleState($module['id'], ModuleManager::STATE_ACTIVE);
            } else {
                // Module valid, installed, and not active
                $modules->setModuleState($module['id'], ModuleManager::STATE_NOT_ACTIVE);
            }
        }

        foreach ($modules->getModules() as $id => $info) {
            if (!$modules->moduleHasState($id)) {
                // Module in filesystem but not installed
                $modules->setModuleState($id, ModuleManager::STATE_NOT_INSTALLED);
            }
        }

        return $modules;
    }
}
