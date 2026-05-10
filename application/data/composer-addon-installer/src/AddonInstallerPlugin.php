<?php

namespace Omeka\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

/**
 * Composer plugin for Omeka S add-on installation.
 *
 * Registers the AddonInstaller for modules and themes.
 */
class AddonInstallerPlugin implements PluginInterface
{
    public function activate(Composer $composer, IOInterface $io)
    {
        $installer = new AddonInstaller($io, $composer);
        $composer->getInstallationManager()->addInstaller($installer);
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
    }
}
