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
    public static function getInstallName(PackageInterface $package)
    {
        $extra = $package->getExtra();
        if (isset($extra['install-name'])) {
            return $extra['install-name'];
        }

        $packageName = $package->getPrettyName();
        $slashPos = strpos($packageName, '/');
        if ($slashPos === false) {
            throw new \InvalidArgumentException('Addon package names must contain a slash');
        }

        $addonName = substr($packageName, $slashPos + 1);
        return $addonName;
    }

    /**
     * {@inheritDoc}
     */
    public function getInstallPath(PackageInterface $package)
    {
        $addonName = static::getInstallName($package);
        switch ($package->getType()) {
            case 'omeka-s-theme':
                return 'themes/' . $addonName;
            case 'omeka-s-module':
                return 'modules/' . $addonName;
            default:
                throw new \InvalidArgumentException('Invalid Omeka S addon package type');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($packageType)
    {
        return in_array($packageType, ['omeka-s-theme', 'omeka-s-module']);
    }
}
