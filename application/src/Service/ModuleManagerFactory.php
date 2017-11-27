<?php
namespace Omeka\Service;

require_once OMEKA_PATH . '/application/Module.php';

use DirectoryIterator;
use Composer\Semver\Comparator;
use Composer\Semver\Semver;
use Interop\Container\ContainerInterface;
use Omeka\Module as CoreModule;
use Omeka\Module\Manager as ModuleManager;
use SplFileInfo;
use Zend\Config\Reader\Ini as IniReader;
use Zend\ServiceManager\Factory\FactoryInterface;

/**
 * Factory for creating Omeka's module manager
 */
class ModuleManagerFactory implements FactoryInterface
{
    /**
     * Create the module manager
     *
     * @param ContainerInterface $serviceLocator
     * @return ModuleManager
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $manager = new ModuleManager($serviceLocator);
        $iniReader = new IniReader;
        $connection = $serviceLocator->get('Omeka\Connection');

        // Get all modules from the filesystem.
        foreach (new DirectoryIterator(OMEKA_PATH . '/modules') as $dir) {

            // Module must be a directory
            if (!$dir->isDir() || $dir->isDot()) {
                continue;
            }

            $module = $manager->registerModule($dir->getBasename());

            // Module directory must contain config/module.ini
            $iniFile = new SplFileInfo($dir->getPathname() . '/config/module.ini');
            if (!$iniFile->isReadable() || !$iniFile->isFile()) {
                $module->setState(ModuleManager::STATE_INVALID_INI);
                continue;
            }

            $ini = $iniReader->fromFile($iniFile->getRealPath());

            // The INI configuration must be under the [info] header.
            if (!isset($ini['info'])) {
                $module->setState(ModuleManager::STATE_INVALID_INI);
                continue;
            }

            $module->setIni($ini['info']);

            // Module INI must be valid
            if (!$manager->iniIsValid($module)) {
                $module->setState(ModuleManager::STATE_INVALID_INI);
                continue;
            }

            // Module directory must contain Module.php
            $moduleFile = new SplFileInfo($dir->getPathname() . '/Module.php');
            if (!$moduleFile->isReadable() || !$moduleFile->isFile()) {
                $module->setState(ModuleManager::STATE_INVALID_MODULE);
                continue;
            }

            $omekaConstraint = $module->getIni('omeka_version_constraint');
            if ($omekaConstraint !== null && !Semver::satisfies(CoreModule::VERSION, $omekaConstraint)) {
                $module->setState(ModuleManager::STATE_INVALID_OMEKA_VERSION);
                continue;
            }

            // Module class must extend Omeka\Module\AbstractModule
            require_once $moduleFile->getRealPath();
            $moduleClass = $dir->getBasename() . '\Module';
            if (!class_exists($moduleClass)
                || !is_subclass_of($moduleClass, 'Omeka\Module\AbstractModule')
            ) {
                $module->setState(ModuleManager::STATE_INVALID_MODULE);
                continue;
            }
        }

        // Get all modules from the database, if installed.
        $dbModules = [];
        $status = $serviceLocator->get('Omeka\Status');
        try {
            $statement = $connection->prepare("SELECT * FROM module");
            $statement->execute();
            $dbModules = $statement->fetchAll();
            $status->setIsInstalled(true);
        } catch (\Exception $e) {
            // If the module table is not found we can assume that the
            // application is not installed.
            $status->setIsInstalled(false);
        }

        foreach ($dbModules as $moduleRow) {
            if (!$manager->isRegistered($moduleRow['id'])) {
                // Module installed but not in filesystem
                $module = $manager->registerModule($moduleRow['id']);
                $module->setDb($moduleRow);
                $module->setState(ModuleManager::STATE_NOT_FOUND);
                continue;
            }

            $module = $manager->getModule($moduleRow['id']);
            $module->setDb($moduleRow);

            if ($module->getState()) {
                // Module already has state.
                continue;
            }

            $moduleIni = $module->getIni();
            if (Comparator::greaterThan($moduleIni['version'], $moduleRow['version'])) {
                // Module in filesystem is newer version than the installed one.
                $module->setState(ModuleManager::STATE_NEEDS_UPGRADE);
                continue;
            }

            if ($moduleRow['is_active']) {
                // Module valid, installed, and active
                $module->setState(ModuleManager::STATE_ACTIVE);
            } else {
                // Module valid, installed, and not active
                $module->setState(ModuleManager::STATE_NOT_ACTIVE);
            }
        }

        foreach ($manager->getModules() as $id => $module) {
            if (!$module->getState()) {
                // Module in filesystem but not installed
                $module->setState(ModuleManager::STATE_NOT_INSTALLED);
            }
        }

        return $manager;
    }
}
