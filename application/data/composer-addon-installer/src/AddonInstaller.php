<?php
namespace Omeka\Composer;

use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;

class AddonInstaller extends LibraryInstaller
{
    /**
     * {@inheritDoc}
     */
    public function getInstallPath(PackageInterface $package)
    {
        $prettyName = $package->getPrettyName();
        $slashPos = strpos($package->getPrettyName(), '/');
        if ($slashPos === false) {
            throw new \InvalidArgumentException('Addon package names must contain a slash');
        }
        $addonName = substr($prettyName, $slashPos + 1);
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
