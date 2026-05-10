<?php
define('OMEKA_PATH', __DIR__);
chdir(OMEKA_PATH);
date_default_timezone_set('UTC');

require 'vendor/autoload.php';

/*
 * Autoloader to prioritize local modules/ over Composer's composer-addons/modules/.
 *
 * When a module exists in both locations, this ensures classes are loaded
 * from modules/ (local) instead of composer-addons/modules/ (Composer).
 *
 * Registered AFTER Composer with prepend=true to run BEFORE Composer.
 */
spl_autoload_register(function ($class) {
    // Extract module namespace (first segment)
    $pos = strpos($class, '\\');
    if ($pos === false) {
        return false;
    }
    $moduleNamespace = substr($class, 0, $pos);

    // Check for conflict: module exists in both locations
    $localModule = OMEKA_PATH . '/modules/' . $moduleNamespace;
    $addonModule = OMEKA_PATH . '/composer-addons/modules/' . $moduleNamespace;

    // Only intervene if local exists as real dir (not symlink) AND addon exists
    if (!is_dir($localModule) || is_link($localModule) || !is_dir($addonModule)) {
        return false; // No conflict, let Composer handle it
    }

    // PSR-4: ModuleName\Foo\Bar -> modules/ModuleName/src/Foo/Bar.php
    $relativePath = str_replace('\\', '/', substr($class, $pos + 1));
    $file = $localModule . '/src/' . $relativePath . '.php';

    if (file_exists($file)) {
        require_once $file;
        return true;
    }

    return false;
}, true, true); // prepend=true to run BEFORE Composer
