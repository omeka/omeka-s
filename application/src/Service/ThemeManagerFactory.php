<?php
namespace Omeka\Service;

use DirectoryIterator;
use SplFileInfo;
use Composer\Semver\Semver;
use Omeka\Module as CoreModule;
use Omeka\Site\Theme\Manager as ThemeManager;
use Omeka\Site\Theme\Theme;
use Zend\Config\Reader\Ini as IniReader;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ThemeManagerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $manager = new ThemeManager;
        $iniReader = new IniReader;

        // Get all themes from the filesystem.
        foreach (new DirectoryIterator(OMEKA_PATH . '/themes') as $dir) {

            // Theme must be a directory
            if (!$dir->isDir() || $dir->isDot()) {
                continue;
            }

            $theme = $manager->registerTheme($dir->getBasename());

            // Theme directory must contain config/module.ini
            $iniFile = new SplFileInfo($dir->getPathname() . '/config/theme.ini');
            if (!$iniFile->isReadable() || !$iniFile->isFile()) {
                $theme->setState(ThemeManager::STATE_INVALID_INI);
                continue;
            }

            $ini = $iniReader->fromFile($iniFile->getRealPath());

            // The INI configuration must be under the [info] header.
            if (!isset($ini['info'])) {
                $theme->setState(ThemeManager::STATE_INVALID_INI);
                continue;
            }
            $configSpec = [];
            if (isset($ini['config'])) {
                $configSpec = $ini['config'];
            }

            $theme->setIni($ini['info']);
            $theme->setConfigSpec($configSpec);

            // Theme INI must be valid
            if (!$manager->iniIsValid($theme)) {
                $theme->setState(ThemeManager::STATE_INVALID_INI);
                continue;
            }

            $omekaConstraint = $theme->getIni('omeka_version_constraint');
            if ($omekaConstraint !== null && !Semver::satisfies(CoreModule::VERSION, $omekaConstraint)) {
                $theme->setState(ThemeManager::STATE_INVALID_OMEKA_VERSION);
                continue;
            }

            $theme->setState(ThemeManager::STATE_ACTIVE);
        }

        // Note that, unlike the ModuleManagerFactory, this does not register
        // themes that exist in the database but have no corresponding directory
        // in the filesystem. Instead, we handle such a circumstance when
        // preparing the site in an MVC listener.

        return $manager;
    }
}
