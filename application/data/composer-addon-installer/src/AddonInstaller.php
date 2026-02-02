<?php
namespace Omeka\Composer;

use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;

class AddonInstaller extends LibraryInstaller
{
    /**
     * Gets the name this package is to be installed with, either from the
     * <pre>extra.install-name</pre> property or the package name.
     *
     * @return string
     */
    public static function getInstallName(PackageInterface $package): string
    {
        $extra = $package->getExtra();
        if (isset($extra['install-name'])) {
            return $extra['install-name'];
        }

        $packageName = $package->getPrettyName();
        $slashPos = strpos($packageName, '/');
        if ($slashPos === false) {
            throw new \InvalidArgumentException('Addon package names must contain a slash'); // @translate
        }

        $addonName = substr($packageName, $slashPos + 1);
        return $addonName;
    }

    public function getInstallPath(PackageInterface $package): string
    {
        $addonName = static::getInstallName($package);
        switch ($package->getType()) {
            case 'omeka-s-module':
                return 'addons/modules/' . $addonName;
            case 'omeka-s-theme':
                return 'addons/themes/' . $addonName;
            default:
                throw new \InvalidArgumentException('Invalid Omeka S addon package type'); // @translate
        }
    }

    public function supports($packageType): bool
    {
        return $packageType === 'omeka-s-module'
            || $packageType === 'omeka-s-theme';
    }
}
