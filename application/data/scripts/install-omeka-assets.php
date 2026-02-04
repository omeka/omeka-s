#!/usr/bin/env php
<?php
/**
 * Install omeka-assets for modules/themes installed via git clone.
 *
 * For add-ons installed via git clone in modules/ or themes/, external assets
 * (js, css, etc.) defined in composer.json are not downloaded automatically.
 * This script reads the add-on composer.json and downloads the assets.
 *
 * Note: Add-ons installed via `composer require` (in composer-addons/modules/ or
 * composer-addons/themes/) have their assets downloaded automatically.
 *
 * Usage:
 *   php application/data/scripts/install-omeka-assets.php [module-or-theme-name]
 *
 * Examples:
 *   php application/data/scripts/install-omeka-assets.php Common
 *   php application/data/scripts/install-omeka-assets.php --all
 *   php application/data/scripts/install-omeka-assets.php --theme default
 *
 * Options:
 *   --all       Install assets for all modules and themes
 *   --theme     Specify a theme instead of a module
 *   --force     Re-download even if assets already exist
 */

require dirname(__DIR__, 3) . '/bootstrap.php';

use Omeka\Module\OmekaAssetsInstaller;

// Create a simple CLI logger
$logger = new class implements \Laminas\Log\LoggerInterface {
    public function emerg($message, $extra = [])
    {
        echo "[EMERG] $message\n";
    }
    public function alert($message, $extra = [])
    {
        echo "[ALERT] $message\n";
    }
    public function crit($message, $extra = [])
    {
        echo "[CRIT] $message\n";
    }
    public function err($message, $extra = [])
    {
        echo "[ERROR] $message\n";
    }
    public function warn($message, $extra = [])
    {
        echo "[WARN] $message\n";
    }
    public function notice($message, $extra = [])
    {
        echo "[NOTICE] $message\n";
    }
    public function info($message, $extra = [])
    {
        echo "[INFO] $message\n";
    }
    public function debug($message, $extra = [])
    {
        echo "[DEBUG] $message\n";
    }
};

$installer = new OmekaAssetsInstaller($logger);

// Parse arguments
$args = array_slice($argv, 1);
$all = false;
$isTheme = false;
$force = false;
$names = [];

foreach ($args as $arg) {
    if ($arg === '--all') {
        $all = true;
    } elseif ($arg === '--theme') {
        $isTheme = true;
    } elseif ($arg === '--force') {
        $force = true;
    } elseif (strpos($arg, '--') !== 0) {
        $names[] = $arg;
    }
}

if (empty($names) && !$all) {
    echo "Usage: php application/data/scripts/install-omeka-assets.php [options] [name]\n";
    echo "\nOptions:\n";
    echo "  --all       Install assets for all modules and themes\n";
    echo "  --theme     Specify a theme instead of a module\n";
    echo "  --force     Re-download even if assets already exist\n";
    echo "\nExamples:\n";
    echo "  php application/data/scripts/install-omeka-assets.php Common\n";
    echo "  php application/data/scripts/install-omeka-assets.php --theme default\n";
    echo "  php application/data/scripts/install-omeka-assets.php --all\n";
    exit(1);
}

// Get paths to scan
$paths = [];

if ($all) {
    // Scan all module and theme directories
    $moduleDirs = [
        OMEKA_PATH . '/modules',
        OMEKA_PATH . '/composer-addons/modules',
    ];
    $themeDirs = [
        OMEKA_PATH . '/themes',
        OMEKA_PATH . '/composer-addons/themes',
    ];

    foreach ($moduleDirs as $dir) {
        if (is_dir($dir)) {
            foreach (new DirectoryIterator($dir) as $entry) {
                if ($entry->isDot() || !$entry->isDir()) {
                    continue;
                }
                $paths[$entry->getBasename()] = $entry->getPathname();
            }
        }
    }

    foreach ($themeDirs as $dir) {
        if (is_dir($dir)) {
            foreach (new DirectoryIterator($dir) as $entry) {
                if ($entry->isDot() || !$entry->isDir()) {
                    continue;
                }
                $paths['theme:' . $entry->getBasename()] = $entry->getPathname();
            }
        }
    }
} else {
    foreach ($names as $name) {
        if ($isTheme) {
            $possiblePaths = [
                OMEKA_PATH . '/themes/' . $name,
                OMEKA_PATH . '/composer-addons/themes/' . $name,
            ];
        } else {
            $possiblePaths = [
                OMEKA_PATH . '/modules/' . $name,
                OMEKA_PATH . '/composer-addons/modules/' . $name,
            ];
        }

        $found = false;
        foreach ($possiblePaths as $path) {
            if (is_dir($path)) {
                $paths[$name] = $path;
                $found = true;
                break;
            }
        }

        if (!$found) {
            echo "Error: " . ($isTheme ? 'Theme' : 'Module') . " '$name' not found.\n";
            exit(1);
        }
    }
}

// Process each path
$totalInstalled = 0;
$totalSkipped = 0;
$totalFailed = 0;

foreach ($paths as $name => $path) {
    $composerJson = $path . '/composer.json';
    if (!file_exists($composerJson)) {
        continue;
    }

    $json = json_decode(file_get_contents($composerJson), true);
    if (empty($json['extra']['omeka-assets'])) {
        continue;
    }

    echo "Processing $name...\n";

    if ($force) {
        // Remove existing assets to force re-download
        foreach ($json['extra']['omeka-assets'] as $dest => $url) {
            $destPath = $path . '/' . ltrim($dest, '/');
            if (substr($dest, -1) === '/') {
                if (is_dir($destPath)) {
                    echo "  Removing $dest for re-download\n";
                    removeDirectory($destPath);
                }
            } else {
                if (file_exists($destPath)) {
                    echo "  Removing $dest for re-download\n";
                    unlink($destPath);
                }
            }
        }
    }

    $result = $installer->installFromPath($path, $name);

    if ($result) {
        $totalInstalled++;
    } else {
        $totalFailed++;
    }
}

echo "\nDone. Installed: $totalInstalled, Failed: $totalFailed\n";

exit($totalFailed > 0 ? 1 : 0);

/**
 * Recursively remove a directory.
 */
function removeDirectory(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    $entries = array_diff(scandir($dir), ['.', '..']);
    foreach ($entries as $entry) {
        $path = $dir . '/' . $entry;
        if (is_dir($path)) {
            removeDirectory($path);
        } else {
            @unlink($path);
        }
    }
    @rmdir($dir);
}
