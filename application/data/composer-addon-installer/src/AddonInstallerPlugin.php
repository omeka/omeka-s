<?php

namespace Omeka\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Util\ProcessExecutor;

/**
 * Composer plugin for Omeka S add-on installation.
 *
 * Registers the AddonInstaller and handles standalone modules that need their
 * own vendor/ directory.
 */
class AddonInstallerPlugin implements PluginInterface, EventSubscriberInterface
{
    /** @var Composer */
    protected $composer;

    /** @var IOInterface */
    protected $io;

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;

        $installer = new AddonInstaller($io, $composer);
        $composer->getInstallationManager()->addInstaller($installer);
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
    }

    public static function getSubscribedEvents()
    {
        return [
            PackageEvents::POST_PACKAGE_INSTALL => 'onPostPackageInstall',
            PackageEvents::POST_PACKAGE_UPDATE => 'onPostPackageUpdate',
            PackageEvents::PRE_PACKAGE_UNINSTALL => 'onPrePackageUninstall',
        ];
    }

    /**
     * Handle pre-uninstall for packages.
     */
    public function onPrePackageUninstall(PackageEvent $event)
    {
        $package = $event->getOperation()->getPackage();
        $this->removeCommonModuleSymlink($package);
    }

    /**
     * Remove Common module symlink when uninstalling.
     */
    protected function removeCommonModuleSymlink($package)
    {
        if ($package->getName() !== 'daniel-km/omeka-s-module-common') {
            return;
        }

        $localPath = 'modules/Common';

        if (is_link($localPath)) {
            if (@unlink($localPath)) {
                $this->io->write(sprintf(
                    '<info>Removed symlink %s</info>',
                    $localPath
                ));
            }
        }
    }

    /**
     * Handle post-install for packages.
     */
    public function onPostPackageInstall(PackageEvent $event)
    {
        $package = $event->getOperation()->getPackage();
        $this->handleStandalonePackage($package);
        $this->handleCommonModuleSymlink($package);
    }

    /**
     * Handle post-update for packages.
     */
    public function onPostPackageUpdate(PackageEvent $event)
    {
        $package = $event->getOperation()->getTargetPackage();
        $this->handleStandalonePackage($package);
        $this->handleCommonModuleSymlink($package);
    }

    /**
     * Create symlink for Common module if not present in modules/.
     *
     * Common module is a special case: many modules depend on its root-level
     * files (TraitModule.php, etc.) via require_once with file paths like:
     *   require_once dirname(__DIR__) . '/Common/TraitModule.php';
     *
     * When Common is installed via Composer to addons/modules/Common/, this
     * path doesn't work. A symlink modules/Common -> addons/modules/Common
     * ensures backward compatibility.
     */
    protected function handleCommonModuleSymlink($package)
    {
        // Only handle Common module
        if ($package->getName() !== 'daniel-km/omeka-s-module-common') {
            return;
        }

        $localPath = 'modules/Common';

        // Don't create symlink if a real directory exists (local override)
        if (is_dir($localPath) && !is_link($localPath)) {
            return;
        }

        $installPath = $this->composer->getInstallationManager()->getInstallPath($package);
        $relativePath = '../' . $installPath;

        // Update existing symlink if target changed
        if (is_link($localPath)) {
            $currentTarget = readlink($localPath);
            if ($currentTarget === $relativePath) {
                return;
            }
            unlink($localPath);
        }

        // Create symlink
        if (@symlink($relativePath, $localPath)) {
            $this->io->write(sprintf(
                '<info>Created symlink %s -> %s for backward compatibility</info>',
                $localPath,
                $relativePath
            ));
        } else {
            $this->io->writeError(sprintf(
                '<warning>Could not create symlink %s -> %s</warning>',
                $localPath,
                $relativePath
            ));
        }
    }

    /**
     * If package is standalone, run composer install in its directory.
     *
     * Standalone packages have extra.standalone = true in their composer.json.
     * This allows them to maintain their own vendor/ directory with specific
     * dependency versions, isolated from the root project.
     */
    protected function handleStandalonePackage($package)
    {
        if (!AddonInstaller::isStandalone($package)) {
            return;
        }

        $type = $package->getType();
        if (!in_array($type, ['omeka-s-module', 'omeka-s-theme'])) {
            return;
        }

        $installPath = $this->composer->getInstallationManager()->getInstallPath($package);
        $composerJson = $installPath . '/composer.json';

        if (!file_exists($composerJson)) {
            $this->io->writeError(sprintf(
                '<warning>Standalone package %s has no composer.json, skipping vendor install</warning>',
                $package->getPrettyName()
            ));
            return;
        }

        $this->io->write(sprintf(
            '<info>Installing standalone dependencies for %s...</info>',
            $package->getPrettyName()
        ));

        $process = new ProcessExecutor($this->io);
        $command = sprintf(
            'cd %s && composer install --no-dev --no-interaction --quiet 2>&1',
            escapeshellarg($installPath)
        );

        $exitCode = $process->execute($command, $output);

        if ($exitCode !== 0) {
            $this->io->writeError(sprintf(
                '<warning>Failed to install standalone dependencies for %s: %s</warning>',
                $package->getPrettyName(),
                $output
            ));
        } else {
            $this->io->write(sprintf(
                '<info>Standalone dependencies installed for %s</info>',
                $package->getPrettyName()
            ));
        }
    }
}
