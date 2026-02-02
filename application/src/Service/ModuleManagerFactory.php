<?php
namespace Omeka\Service;

require_once OMEKA_PATH . '/application/Module.php';

use DirectoryIterator;
use Composer\Semver\Comparator;
use Composer\Semver\Semver;
use Interop\Container\ContainerInterface;
use Omeka\Module as CoreModule;
use Omeka\Module\InfoReader;
use Omeka\Module\Manager as ModuleManager;
use SplFileInfo;
use Laminas\ServiceManager\Factory\FactoryInterface;

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
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, ?array $options = null)
    {
        $manager = new ModuleManager($serviceLocator);
        $infoReader = new InfoReader();
        $connection = $serviceLocator->get('Omeka\Connection');

        // Load installed.json once for all Composer-installed modules.
        $infoReader->loadComposerInstalled();

        // Get all modules from the filesystem.
        // Scan local modules first so they take precedence over add-ons.
        // Note: addons/modules/ is scanned even though installed.json contains
        // the module list. This ensures Module.php exists, handles out-of-sync
        // cases, and maintains consistency with how modules/ works. The
        // installed.json is only used for metadata (name, version, etc.), not
        // for discovering which modules are installed.
        $modulePaths = [
            OMEKA_PATH . '/modules',
            OMEKA_PATH . '/addons/modules',
        ];
        $registered = [];
        foreach ($modulePaths as $modulePath) {
            if (!is_dir($modulePath)) {
                continue;
            }
            foreach (new DirectoryIterator($modulePath) as $dir) {

                // Module must be a directory.
                if (!$dir->isDir() || $dir->isDot()) {
                    continue;
                }

                // Skip if module already registered (local takes precedence).
                $moduleId = $dir->getBasename();
                if (isset($registered[$moduleId])) {
                    continue;
                }
                $registered[$moduleId] = true;

                $module = $manager->registerModule($moduleId);

                // Only use installed.json for modules in addons/modules/.
                // Local modules in modules/ must read their own files.
                $info = null;
                $isComposerAddon = strpos($dir->getPathname(), '/addons/modules/') !== false;
                if ($isComposerAddon) {
                    // Try installed.json first to avoid checking compatibility.
                    $info = $infoReader->getFromComposerInstalled($moduleId, 'module');
                }
                if (empty($info)) {
                    // Fallback: read from individual files (manual modules).
                    $info = $infoReader->read($dir->getPathname(), 'module');
                }

                // Module must have valid info.
                if (!$infoReader->isValid($info)) {
                    $module->setState(ModuleManager::STATE_INVALID_INI);
                    continue;
                }

                $module->setIni($info);

                // Module directory must contain Module.php.
                $moduleFile = new SplFileInfo($dir->getPathname() . '/Module.php');
                if (!$moduleFile->isReadable() || !$moduleFile->isFile()) {
                    $module->setState(ModuleManager::STATE_INVALID_MODULE);
                    continue;
                }
                $module->setModuleFilePath($moduleFile->getRealPath());

                // Check Omeka version constraint only for manual modules.
                // Composer add-ons use require.omeka/omeka-s for version checking.
                if (!$isComposerAddon) {
                    $omekaConstraint = $module->getIni('omeka_version_constraint');
                    if ($omekaConstraint !== null && !Semver::satisfies(CoreModule::VERSION, $omekaConstraint)) {
                        $module->setState(ModuleManager::STATE_INVALID_OMEKA_VERSION);
                        continue;
                    }
                }
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

            if (!$moduleRow['is_active']) {
                // Module valid, installed, but not active
                $module->setState(ModuleManager::STATE_NOT_ACTIVE);
                continue;
            }

            // Module class must extend Omeka\Module\AbstractModule
            // This check is delayed here to avoid loading non-active modules.
            require_once $module->getModuleFilePath();
            $moduleClass = $module->getId() . '\Module';
            if (!class_exists($moduleClass)
                || !is_subclass_of($moduleClass, 'Omeka\Module\AbstractModule')
            ) {
                $module->setState(ModuleManager::STATE_INVALID_MODULE);
                continue;
            }

            // Module valid, installed, and active.
            $module->setState(ModuleManager::STATE_ACTIVE);
        }

        foreach ($manager->getModules() as $id => $module) {
            if (!$module->getState()) {
                // Module in filesystem but not installed.
                $module->setState(ModuleManager::STATE_NOT_INSTALLED);
            }
        }

        // Reorder modules.
        $manager->sortModules();

        return $manager;
    }
}
