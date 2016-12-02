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

            $ini = $iniReader->fromFile($iniFile->getRealPath());

            $configSpec = [];
            if (isset($ini['config'])) {
                $configSpec = $ini['config'];
                unset($ini['config']);
            }
            // INI configuration may be under the [info] header.
            if (isset($ini['info'])) {
                $ini = $ini['info'];
            }

            if (!$manager->iniIsValid($ini)) {
                continue;
            }

            $manager->registerTheme(new Theme($dir->getBasename(), $ini, $configSpec));
        }

        return $manager;
    }
}
