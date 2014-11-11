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
        $modules    = new ModuleManager;
        $iniReader  = new IniReader;
        $connection = $serviceLocator->get('Omeka\Connection');

        // Get all modules from the filesystem.
        foreach (new DirectoryIterator(OMEKA_PATH . '/module') as $dir) {

            // Module must be a directory
            if (!$dir->isDir() || $dir->isDot()) {
                continue;
            }

            $modules->registerModule($dir->getBasename());

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

        // Get all modules from the database, if installed.
        $dbModules = array();
        if ($serviceLocator->get('Omeka\Status')->isInstalled()) {
            $statement = $connection->prepare("SELECT * FROM module");
            $statement->execute();
            $dbModules = $statement->fetchAll();
        }

        foreach ($dbModules as $moduleRow) {

            if (!$modules->moduleIsRegistered($moduleRow['id'])) {
                // Module installed but not in filesystem
                $modules->registerModule($moduleRow['id']);
                $modules->setModuleDb($moduleRow['id'], $moduleRow);
                $modules->setModuleState($moduleRow['id'], ModuleManager::STATE_NOT_FOUND);
                continue;
            }

            $modules->setModuleDb($moduleRow['id'], $moduleRow);

            if ($modules->moduleHasState($moduleRow['id'])) {
                // Module already has state.
                continue;
            }

            $moduleIni = $modules->getModuleIni($moduleRow['id']);
            if (version_compare($moduleIni['version'], $moduleRow['version'], '>')) {
                // Module in filesystem is newer version than the installed one.
                $modules->setModuleState($moduleRow['id'], ModuleManager::STATE_NEEDS_UPGRADE);
                continue;
            }

            if ($moduleRow['is_active']) {
                // Module valid, installed, and active
                $modules->setModuleState($moduleRow['id'], ModuleManager::STATE_ACTIVE);
            } else {
                // Module valid, installed, and not active
                $modules->setModuleState($moduleRow['id'], ModuleManager::STATE_NOT_ACTIVE);
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
