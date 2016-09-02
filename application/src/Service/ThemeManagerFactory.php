<?php
namespace Omeka\Service;

use DirectoryIterator;
use SplFileInfo;
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
        $connection = $serviceLocator->get('Omeka\Connection');

        // Get all themes from the filesystem.
        foreach (new DirectoryIterator(OMEKA_PATH . '/themes') as $dir) {

            // Theme must be a directory
            if (!$dir->isDir() || $dir->isDot()) {
                continue;
            }

            // Theme directory must contain config/module.ini
            $iniFile = new SplFileInfo($dir->getPathname() . '/config/theme.ini');
            if (!$iniFile->isReadable() || !$iniFile->isFile()) {
                continue;
            }

            // Theme INI must be valid
            $ini = $iniReader->fromFile($iniFile->getRealPath());
            if (!$manager->iniIsValid($ini)) {
                continue;
            }

            $manager->registerTheme(new Theme($dir->getBasename(), $ini));
        }

        return $manager;
    }
}
