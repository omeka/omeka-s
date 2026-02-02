<?php
namespace Omeka\Service;

use DirectoryIterator;
use Composer\Semver\Semver;
use Omeka\Module as CoreModule;
use Omeka\Module\InfoReader;
use Omeka\Site\Theme\Manager as ThemeManager;
use Laminas\Config\Reader\Ini as IniReader;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ThemeManagerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, ?array $options = null)
    {
        // Prepare injection of module templates.
        $config = $serviceLocator->get('Config');
        $modulePageTemplates = $config['page_templates'];
        $moduleBlockTemplates = $config['block_templates'];

        $manager = new ThemeManager;
        $infoReader = new InfoReader();
        $iniReader = new IniReader;

        // Load installed.json once for all Composer-installed themes.
        $infoReader->loadComposerInstalled();

        // Get all themes from the filesystem.
        // Scan local themes first so they take precedence over add-ons.
        $themePaths = [
            'themes' => OMEKA_PATH . '/themes',
            'addons/themes' => OMEKA_PATH . '/addons/themes',
        ];
        $registered = [];
        foreach ($themePaths as $basePath => $themePath) {
            if (!is_dir($themePath)) {
                continue;
            }
            foreach (new DirectoryIterator($themePath) as $dir) {

                // Theme must be a directory
                if (!$dir->isDir() || $dir->isDot()) {
                    continue;
                }

                // Skip if theme already registered (local takes precedence).
                $themeId = $dir->getBasename();
                if (isset($registered[$themeId])) {
                    continue;
                }
                $registered[$themeId] = true;

                $theme = $manager->registerTheme($themeId);
                $theme->setBasePath($basePath);

                // Try installed.json first, avoiding reading file for composer.
                $info = $infoReader->getFromComposerInstalled($themeId, 'theme');

                // Fallback: read from individual files (manual themes).
                if ($info === null) {
                    $info = $infoReader->read($dir->getPathname(), 'theme');
                }

                // Theme must have valid info
                if (!$infoReader->isValid($info)) {
                    $theme->setState(ThemeManager::STATE_INVALID_INI);
                    continue;
                }

                // Read config spec from theme.ini [config] section if present.
                // This is always needed, even for Composer themes, as [config]
                // defines form elements and is not in composer.json.
                $configSpec = [];
                $iniFile = $dir->getPathname() . '/config/theme.ini';
                if (is_file($iniFile) && is_readable($iniFile)) {
                    try {
                        $ini = $iniReader->fromFile($iniFile);
                        if (isset($ini['config'])) {
                            $configSpec = $ini['config'];
                        }
                    } catch (\Exception $e) {
                        // Ignore ini read errors for config section.
                    }
                }

                $theme->setIni($info);
                $theme->setConfigSpec($configSpec);

                $omekaConstraint = $theme->getIni('omeka_version_constraint');
                if ($omekaConstraint !== null && !Semver::satisfies(CoreModule::VERSION, $omekaConstraint)) {
                    $theme->setState(ThemeManager::STATE_INVALID_OMEKA_VERSION);
                    continue;
                }

                $theme->setState(ThemeManager::STATE_ACTIVE);

                // Inject module templates, with priority to theme templates.
                // Take care of merge with duplicate template keys.
                if (count($modulePageTemplates)) {
                    $configSpec['page_templates'] = empty($configSpec['page_templates'])
                        ? $modulePageTemplates
                        : array_replace($modulePageTemplates, $configSpec['page_templates']);
                }
                if (count($moduleBlockTemplates)) {
                    $configSpec['block_templates'] = empty($configSpec['block_templates'])
                        ? $moduleBlockTemplates
                        // Array_merge_recursive() converts duplicate keys to array.
                        // Array_map() removes keys.
                        : array_replace_recursive($moduleBlockTemplates, $configSpec['block_templates']);
                }
                $theme->setConfigSpec($configSpec);
            }
        }

        // Note that, unlike the ModuleManagerFactory, this does not register
        // themes that exist in the database but have no corresponding directory
        // in the filesystem. Instead, we handle such a circumstance when
        // preparing the site in an MVC listener.

        return $manager;
    }
}
