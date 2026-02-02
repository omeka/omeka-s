<?php

namespace Omeka\OmekaAssets;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Util\Filesystem;
use Composer\Util\HttpDownloader;
use Composer\Util\ProcessExecutor;

/**
 * Composer plugin to download external assets for Omeka S modules and themes.
 *
 * This plugin handles the "extra.omeka-assets" configuration in composer.json,
 * downloading external files (JS, CSS, etc.) during package installation.
 *
 * Format:
 * "extra": {
 *     "omeka-assets": {
 *         "asset/vendor/lib/file.min.js": "https://example.com/v3.4.0/file.min.js",
 *         "asset/vendor/lib/": "https://example.com/v3.4.1/archive.zip",
 *         "asset/vendor/scripts/": "https://example.com/script.js"
 *     }
 * }
 *
 * - If destination ends with a filename, download url and rename to that name.
 * - If destination ends with `/` and url has .zip/.tar.gz/.tgz, extract it.
 *   Note: if the archive contains a single root directory, it is stripped.
 * - If destination ends with `/` and url is a file, copy it into that directory.
 */
class OmekaAssetsPlugin implements PluginInterface, EventSubscriberInterface
{
    /** @var Composer */
    protected $composer;

    /** @var IOInterface */
    protected $io;

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
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
        ];
    }

    /**
     * Handle post-install for packages with omeka-assets.
     */
    public function onPostPackageInstall(PackageEvent $event)
    {
        $package = $event->getOperation()->getPackage();
        $this->handleOmekaAssets($package);
    }

    /**
     * Handle post-update for packages with omeka-assets.
     */
    public function onPostPackageUpdate(PackageEvent $event)
    {
        $package = $event->getOperation()->getTargetPackage();
        $this->handleOmekaAssets($package);
    }

    /**
     * Download and install assets defined in extra.omeka-assets.
     */
    protected function handleOmekaAssets($package)
    {
        $extra = $package->getExtra();
        if (empty($extra['omeka-assets']) || !is_array($extra['omeka-assets'])) {
            return;
        }

        $installPath = $this->composer->getInstallationManager()->getInstallPath($package);

        foreach ($extra['omeka-assets'] as $destination => $url) {
            $destPath = $installPath . '/' . ltrim($destination, '/');
            $isDirectory = substr($destination, -1) === '/';
            $isArchive = preg_match('/\.(zip|tar\.gz|tgz)$/i', $url);

            $this->io->write(sprintf(
                '<info>Downloading asset %s for %s...</info>',
                basename($url),
                $package->getPrettyName()
            ));

            try {
                if ($isDirectory && $isArchive) {
                    $this->downloadAndExtract($url, $destPath);
                } elseif ($isDirectory) {
                    // Directory destination + non-archive URL: copy file into directory.
                    $this->downloadFile($url, $destPath . basename($url));
                } else {
                    $this->downloadFile($url, $destPath);
                }
            } catch (\Exception $e) {
                $this->io->writeError(sprintf(
                    '<warning>Failed to download asset %s: %s</warning>',
                    $url,
                    $e->getMessage()
                ));
            }
        }
    }

    /**
     * Download a single file using composer HttpDownloader.
     */
    protected function downloadFile(string $url, string $destPath): void
    {
        $filesystem = new Filesystem();
        $filesystem->ensureDirectoryExists(dirname($destPath));

        $httpDownloader = new HttpDownloader($this->io, $this->composer->getConfig());
        $httpDownloader->copy($url, $destPath);
    }

    /**
     * Download and extract an archive using composer utilities.
     *
     * If the archive contains a single root directory, its contents are
     * extracted directly to the destination (stripping the root directory).
     */
    protected function downloadAndExtract(string $url, string $destPath): void
    {
        $filesystem = new Filesystem();

        $tempFile = sys_get_temp_dir() . '/' . basename($url);
        $tempDir = sys_get_temp_dir() . '/omeka_extract_' . uniqid();

        $filesystem->ensureDirectoryExists($tempDir);

        $httpDownloader = new HttpDownloader($this->io, $this->composer->getConfig());
        $httpDownloader->copy($url, $tempFile);

        // Use composer archive extractor via process.
        $process = new ProcessExecutor($this->io);

        if (preg_match('/\.zip$/i', $url)) {
            // Try unzip command first, fallback to php ZipArchive.
            $command = sprintf('unzip -o -q %s -d %s 2>&1', escapeshellarg($tempFile), escapeshellarg($tempDir));
            if ($process->execute($command) !== 0) {
                // Fallback to ZipArchive if unzip is not available.
                if (!class_exists('ZipArchive')) {
                    $filesystem->unlink($tempFile);
                    $filesystem->removeDirectory($tempDir);
                    throw new \RuntimeException('Cannot extract zip: unzip command failed and ZipArchive not available');
                }
                $zip = new \ZipArchive();
                if ($zip->open($tempFile) !== true) {
                    $filesystem->unlink($tempFile);
                    $filesystem->removeDirectory($tempDir);
                    throw new \RuntimeException('Failed to open zip archive');
                }
                $zip->extractTo($tempDir);
                $zip->close();
            }
        } elseif (preg_match('/\.(tar\.gz|tgz)$/i', $url)) {
            $command = sprintf('tar -xzf %s -C %s 2>&1', escapeshellarg($tempFile), escapeshellarg($tempDir));
            if ($process->execute($command) !== 0) {
                // Fallback to PharData.
                $phar = new \PharData($tempFile);
                $phar->extractTo($tempDir);
            }
        }

        $filesystem->unlink($tempFile);

        // Check if archive has a single root directory and strip it.
        $sourceDir = $this->getArchiveSourceDir($tempDir);

        // Move contents to destination.
        $filesystem->ensureDirectoryExists($destPath);
        $this->moveDirectoryContents($sourceDir, $destPath, $filesystem);

        // Cleanup temp directory.
        $filesystem->removeDirectory($tempDir);
    }

    /**
     * Get the source directory for extraction.
     *
     * If the extracted archive contains a single root directory, return that
     * directory path (to strip the root). Otherwise return the temp directory.
     */
    protected function getArchiveSourceDir(string $tempDir): string
    {
        $entries = array_diff(scandir($tempDir), ['.', '..']);

        // If single entry and it's a directory, use it as source (strip root).
        if (count($entries) === 1) {
            $entry = reset($entries);
            $entryPath = $tempDir . '/' . $entry;
            if (is_dir($entryPath)) {
                return $entryPath;
            }
        }

        return $tempDir;
    }

    /**
     * Move contents from source directory to destination.
     */
    protected function moveDirectoryContents(string $source, string $dest, Filesystem $filesystem): void
    {
        $entries = array_diff(scandir($source), ['.', '..']);

        foreach ($entries as $entry) {
            $srcPath = $source . '/' . $entry;
            $dstPath = $dest . '/' . $entry;

            if (is_dir($srcPath)) {
                $filesystem->ensureDirectoryExists($dstPath);
                $this->moveDirectoryContents($srcPath, $dstPath, $filesystem);
                @rmdir($srcPath);
            } else {
                // Remove existing file if any.
                if (file_exists($dstPath)) {
                    $filesystem->unlink($dstPath);
                }
                rename($srcPath, $dstPath);
            }
        }
    }
}
