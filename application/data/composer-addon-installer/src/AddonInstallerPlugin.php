<?php

namespace Omeka\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class AddonInstallerPlugin implements PluginInterface
{
    public function activate(Composer $composer, IOInterface $io)
    {
        $installer = new AddonInstaller($io, $composer);
        $composer->getInstallationManager()->addInstaller($installer);
    }
}
