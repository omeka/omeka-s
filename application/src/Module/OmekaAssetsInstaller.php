<?php
namespace Omeka\Module;

use Laminas\Log\LoggerInterface;

/**
 * Installs external assets defined in module/theme composer.json.
 *
 * This service handles downloading assets defined in the "extra.omeka-assets"
 * section of a module or theme's composer.json. It is called during module
 * installation and can be called manually during module upgrades.
 *
 * Format of omeka-assets:
 * "extra": {
 *     "omeka-assets": {
 *         "asset/vendor/lib/file.min.js": "https://example.com/v3.4.0/file.min.js",
 *         "asset/vendor/lib/": "https://example.com/v3.4.1/archive.zip",
 *         "asset/vendor/scripts/": "https://example.com/script.js"
 *     }
 * }
 *
 * If destination ends with a filename, download url and rename to that name.
 * If destination ends with `/` and url has .zip/.tar.gz/.tgz, extract it.
 * Note: if the archive contains a single root directory, it is stripped.
 * If destination ends with `/` and url is a file, copy it into that directory.
 *
 * Adapted:
 * @see Omeka\Module\OmekaAssetsInstaller
 * @see Omeka\Composer\AddonInstallerPlugin
 */
class OmekaAssetsInstaller
{
    /** @var LoggerInterface */
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Install omeka-assets for a module.
     *
     * @param Module $module The module to install assets for
     * @return bool True if all assets were installed successfully
     */
    public function install(Module $module): bool
    {
        $modulePath = dirname($module->getModuleFilePath());
        return $this->installFromPath($modulePath, $module->getId());
    }

    /**
     * Install omeka-assets from a module/theme path.
     *
     * @param string $path Path to the module or theme directory
     * @param string|null $name Optional name for logging
     * @return bool True if all assets were installed successfully
     */
    public function installFromPath(string $path, ?string $name = null): bool
    {
        $composerJsonPath = $path . '/composer.json';
        if (!file_exists($composerJsonPath)) {
            return true; // No composer.json, nothing to do
        }

        $composerJson = json_decode(file_get_contents($composerJsonPath), true);
        if (!$composerJson) {
            return true; // Invalid JSON, skip
        }

        $omekaAssets = $composerJson['extra']['omeka-assets'] ?? null;
        if (!$omekaAssets || !is_array($omekaAssets)) {
            return true; // No omeka-assets defined
        }

        $name = $name ?: basename($path);
        $success = true;

        foreach ($omekaAssets as $destination => $url) {
            $destPath = $path . '/' . ltrim($destination, '/');

            // Check if asset already exists
            if ($this->assetExists($destPath, $destination)) {
                $this->logger->info(sprintf(
                    'Asset already exists for %s: %s',
                    $name,
                    $destination
                ));
                continue;
            }

            $this->logger->info(sprintf(
                'Downloading asset for %s: %s',
                $name,
                basename($url)
            ));

            try {
                $isDirectory = substr($destination, -1) === '/';
                $isArchive = (bool) preg_match('/\.(zip|tar\.gz|tgz)$/i', $url);

                if ($isDirectory && $isArchive) {
                    $this->downloadAndExtract($url, $destPath);
                } elseif ($isDirectory) {
                    $this->downloadFile($url, $destPath . basename($url));
                } else {
                    $this->downloadFile($url, $destPath);
                }
            } catch (\Exception $e) {
                $this->logger->err(sprintf(
                    'Failed to download asset %s for %s: %s',
                    $url,
                    $name,
                    $e->getMessage()
                ));
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Check if an asset already exists.
     */
    protected function assetExists(string $destPath, string $destination): bool
    {
        $isDirectory = substr($destination, -1) === '/';

        if ($isDirectory) {
            // For directories, check if the directory exists and is not empty
            if (is_dir($destPath)) {
                $entries = array_diff(scandir($destPath), ['.', '..']);
                return count($entries) > 0;
            }
            return false;
        }

        return file_exists($destPath);
    }

    /**
     * Download a single file.
     */
    protected function downloadFile(string $url, string $destPath): void
    {
        $destDir = dirname($destPath);
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        $content = $this->fetchUrl($url);
        file_put_contents($destPath, $content);
    }

    /**
     * Download and extract an archive.
     */
    protected function downloadAndExtract(string $url, string $destPath): void
    {
        $tempFile = sys_get_temp_dir() . '/' . basename($url);
        $tempDir = sys_get_temp_dir() . '/omeka_extract_' . uniqid();

        mkdir($tempDir, 0755, true);

        try {
            $content = $this->fetchUrl($url);
            file_put_contents($tempFile, $content);

            if (preg_match('/\.zip$/i', $url)) {
                $this->extractZip($tempFile, $tempDir);
            } elseif (preg_match('/\.(tar\.gz|tgz)$/i', $url)) {
                $this->extractTarGz($tempFile, $tempDir);
            }

            @unlink($tempFile);

            // Check if archive has a single root directory and strip it
            $sourceDir = $this->getArchiveSourceDir($tempDir);

            // Move contents to destination
            if (!is_dir($destPath)) {
                mkdir($destPath, 0755, true);
            }
            $this->moveDirectoryContents($sourceDir, $destPath);

            // Cleanup temp directory
            $this->removeDirectory($tempDir);
        } catch (\Exception $e) {
            @unlink($tempFile);
            $this->removeDirectory($tempDir);
            throw $e;
        }
    }

    /**
     * Fetch URL content.
     */
    protected function fetchUrl(string $url): string
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: Omeka S\r\n",
                'follow_location' => true,
                'timeout' => 30,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $content = @file_get_contents($url, false, $context);
        if ($content === false) {
            throw new \RuntimeException('Failed to download: ' . $url);
        }

        return $content;
    }

    /**
     * Extract a zip file.
     */
    protected function extractZip(string $zipFile, string $destDir): void
    {
        // Try command line first
        $command = sprintf(
            'unzip -o -q %s -d %s 2>&1',
            escapeshellarg($zipFile),
            escapeshellarg($destDir)
        );
        exec($command, $output, $exitCode);

        if ($exitCode === 0) {
            return;
        }

        // Fallback to ZipArchive
        if (!class_exists('ZipArchive')) {
            throw new \RuntimeException('Cannot extract zip: unzip command failed and ZipArchive not available');
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipFile) !== true) {
            throw new \RuntimeException('Failed to open zip archive');
        }
        $zip->extractTo($destDir);
        $zip->close();
    }

    /**
     * Extract a tar.gz file.
     */
    protected function extractTarGz(string $tarFile, string $destDir): void
    {
        // Try command line first
        $command = sprintf(
            'tar -xzf %s -C %s 2>&1',
            escapeshellarg($tarFile),
            escapeshellarg($destDir)
        );
        exec($command, $output, $exitCode);

        if ($exitCode === 0) {
            return;
        }

        // Fallback to PharData
        $phar = new \PharData($tarFile);
        $phar->extractTo($destDir);
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

        // If single entry and it's a directory, use it as source (strip root)
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
    protected function moveDirectoryContents(string $source, string $dest): void
    {
        $entries = array_diff(scandir($source), ['.', '..']);

        foreach ($entries as $entry) {
            $srcPath = $source . '/' . $entry;
            $dstPath = $dest . '/' . $entry;

            if (is_dir($srcPath)) {
                if (!is_dir($dstPath)) {
                    mkdir($dstPath, 0755, true);
                }
                $this->moveDirectoryContents($srcPath, $dstPath);
                @rmdir($srcPath);
            } else {
                if (file_exists($dstPath)) {
                    @unlink($dstPath);
                }
                rename($srcPath, $dstPath);
            }
        }
    }

    /**
     * Recursively remove a directory.
     */
    protected function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $entries = array_diff(scandir($dir), ['.', '..']);
        foreach ($entries as $entry) {
            $path = $dir . '/' . $entry;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
