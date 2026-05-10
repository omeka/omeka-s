#!/usr/bin/env php
<?php
/**
 * Install composer dependencies for a git-cloned module or theme.
 *
 * For add-ons installed via git clone in modules/ or themes/, dependencies
 * are not installed automatically. This script reads the add-on composer.json
 * and installs its dependencies via the root Omeka composer.
 *
 * Note: Add-ons installed via `composer require` (in composer-addons/modules/ or
 * composer-addons/themes/) have their dependencies installed automatically.
 *
 * Usage:
 *   php application/data/scripts/install-addon-deps.php ModuleName
 *   php application/data/scripts/install-addon-deps.php --theme theme-name
 *   php application/data/scripts/install-addon-deps.php --dry-run ModuleName
 *
 * Options:
 *   --theme     Specify a theme instead of a module
 *   --dry-run   Show what would be installed without actually installing
 */
$args = array_slice($argv, 1);
$dryRun = false;
$isTheme = false;
$addonName = null;

foreach ($args as $arg) {
    if ($arg === '--dry-run') {
        $dryRun = true;
    } elseif ($arg === '--theme') {
        $isTheme = true;
    } elseif (strpos($arg, '--') !== 0) {
        $addonName = $arg;
    }
}

if (!$addonName) {
    echo "Usage: php application/data/scripts/install-addon-deps.php [--dry-run] [--theme] Name\n";
    echo "\nOptions:\n";
    echo "  --theme     Specify a theme instead of a module\n";
    echo "  --dry-run   Show what would be installed without actually installing\n";
    exit(1);
}

// Find addon path
if ($isTheme) {
    $possiblePaths = [
        dirname(__DIR__, 3) . '/themes/' . $addonName,
        dirname(__DIR__, 3) . '/composer-addons/themes/' . $addonName,
    ];
    $addonType = 'Theme';
} else {
    $possiblePaths = [
        dirname(__DIR__, 3) . '/modules/' . $addonName,
        dirname(__DIR__, 3) . '/composer-addons/modules/' . $addonName,
    ];
    $addonType = 'Module';
}

$addonPath = null;
foreach ($possiblePaths as $path) {
    if (is_dir($path)) {
        $addonPath = $path;
        break;
    }
}

if (!$addonPath) {
    echo "Error: $addonType '$addonName' not found.\n";
    exit(1);
}

$composerJson = $addonPath . '/composer.json';
if (!file_exists($composerJson)) {
    echo "Error: No composer.json found in $addonPath\n";
    exit(1);
}

$json = json_decode(file_get_contents($composerJson), true);
if (!$json) {
    echo "Error: Invalid composer.json\n";
    exit(1);
}

$require = $json['require'] ?? [];

if (empty($require)) {
    echo "No dependencies to install for $addonName.\n";
    exit(0);
}

// Filter out packages provided by Omeka or PHP
$toInstall = [];
foreach ($require as $package => $version) {
    // Skip Omeka core packages (provided by Omeka)
    if (in_array($package, ['omeka/omeka-s', 'omeka/omeka-s-core'])) {
        continue;
    }
    // Skip PHP extensions
    if (strpos($package, 'ext-') === 0) {
        continue;
    }
    // Skip PHP version constraint
    if ($package === 'php') {
        continue;
    }
    $toInstall[$package] = $version;
}

if (empty($toInstall)) {
    echo "No external dependencies to install for $addonName.\n";
    exit(0);
}

echo "Dependencies for $addonName:\n";
foreach ($toInstall as $package => $version) {
    echo "  - $package: $version\n";
}

if ($dryRun) {
    echo "\n[Dry run] Would run:\n";
    echo "  composer require " . implode(' ', array_keys($toInstall)) . "\n";
    exit(0);
}

echo "\nInstalling...\n";

$packages = implode(' ', array_map(function ($pkg, $ver) {
    // Use version constraint if specific, otherwise let composer decide
    if (preg_match('/^\^|~|>=|<=|>|<|\*/', $ver)) {
        return escapeshellarg("$pkg:$ver");
    }
    return escapeshellarg($pkg);
}, array_keys($toInstall), $toInstall));

$command = "cd " . escapeshellarg(dirname(__DIR__, 3)) . " && composer require $packages --no-interaction 2>&1";

echo "Running: composer require " . implode(' ', array_keys($toInstall)) . "\n\n";

passthru($command, $exitCode);

exit($exitCode);
