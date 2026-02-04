<?php
namespace Omeka\Composer;

use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;

/**
 * Composer installer for Omeka S modules and themes.
 *
 * Installs packages to composer-addons/modules/ or composer-addons/themes/ based on type.
 * Name transformations align with composer/installers OmekaSInstaller.
 *
 * Supports extra options:
 * - installer-name: Explicit install name (overrides auto-detection)
 * - standalone: If true, module keeps its own vendor/ directory
 */
class AddonInstaller extends LibraryInstaller
{
    /**
     * Gets the name this package is to be installed with.
     *
     * Priority:
     * 1. extra.installer-name (explicit)
     * 2. Auto-transformation based on package type
     *
     * For modules: removes prefixes/suffixes and converts to CamelCase
     * For themes: removes prefixes/suffixes, keeps lowercase with hyphens
     *
     * @return string
     */
    public static function getInstallName(PackageInterface $package): string
    {
        $extra = $package->getExtra();

        // Support both installer-name (composer/installers) and install-name
        // (legacy).
        if (isset($extra['installer-name'])) {
            return $extra['installer-name'];
        }
        if (isset($extra['install-name'])) {
            return $extra['install-name'];
        }

        $packageName = $package->getPrettyName();
        $slashPos = strpos($packageName, '/');
        if ($slashPos === false) {
            throw new \InvalidArgumentException('Add-on package names must contain a slash'); // @translate
        }

        $name = substr($packageName, $slashPos + 1);
        $type = $package->getType();

        if ($type === 'omeka-s-module') {
            return static::inflectModuleName($name);
        }

        if ($type === 'omeka-s-theme') {
            return static::inflectThemeName($name);
        }

        return $name;
    }

    /**
     * Transform module name: remove prefixes/suffixes, convert to CamelCase.
     *
     * Examples:
     * - omeka-s-module-common → Common
     * - value-suggest → ValueSuggest
     * - bulk-import-module → BulkImport
     * - neatline-omeka-s → Neatline
     * - module-lessonplans → Lessonplans
     *
     * @param string $name
     * @return string
     */
    protected static function inflectModuleName($name): string
    {
        // Remove Omeka prefixes/suffixes.
        $name = preg_replace('/^(omeka-?s?-?)?(module-)?/', '', $name);
        $name = preg_replace('/(-module)?(-omeka-?s?)?$/', '', $name);

        // Convert kebab-case to CamelCase.
        $name = strtr($name, ['-' => ' ']);
        $name = strtr(ucwords($name), [' ' => '']);

        return $name;
    }

    /**
     * Transform theme name: remove prefixes/suffixes, keep lowercase.
     *
     * Examples:
     * - omeka-s-theme-repository → repository
     * - my-custom-theme → my-custom
     * - flavor-theme-omeka → flavor
     *
     * @param string $name
     * @return string
     */
    protected static function inflectThemeName($name): string
    {
        // Remove Omeka prefixes/suffixes.
        $name = preg_replace('/^(omeka-?s?-?)?(theme-)?/', '', $name);
        $name = preg_replace('/(-theme)?(-omeka-?s?)?$/', '', $name);

        return $name;
    }

    /**
     * Check if package wants standalone installation (own vendor/).
     *
     * @param PackageInterface $package
     * @return bool
     */
    public static function isStandalone(PackageInterface $package): bool
    {
        $extra = $package->getExtra();
        return !empty($extra['standalone']);
    }

    public function getInstallPath(PackageInterface $package): string
    {
        $addonName = static::getInstallName($package);
        switch ($package->getType()) {
            case 'omeka-s-module':
                return 'composer-addons/modules/' . $addonName;
            case 'omeka-s-theme':
                return 'composer-addons/themes/' . $addonName;
            default:
                throw new \InvalidArgumentException('Invalid Omeka S add-on package type'); // @translate
        }
    }

    public function supports($packageType): bool
    {
        return $packageType === 'omeka-s-module'
            || $packageType === 'omeka-s-theme';
    }
}
