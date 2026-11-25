<?php
define('OMEKA_PATH', __DIR__);
chdir(OMEKA_PATH);
date_default_timezone_set('UTC');

require 'vendor/autoload.php';

/*
 * Autoloader for class overrides and for local modules.
 *
 * It first loads the classes overridden by Omeka, then it prioritizes local
 * modules/ over the ones managed by Composer in composer-addons/modules/.
 *
 * Registered after Composer with prepend=true to run before Composer.
 */
spl_autoload_register(static function ($class) {
    $overrides = [
        'Doctrine\Common\Proxy\AbstractProxyFactory' => 'AbstractProxyFactory.php',
        'Doctrine\ORM\Proxy\ProxyFactory' => 'ProxyFactory.php',
        'Laminas\Escaper\Escaper' => 'Escaper.php',
        'Laminas\Stdlib\SplPriorityQueue' => 'SplPriorityQueue.php',
    ];
    if (isset($overrides[$class])) {
        require_once OMEKA_PATH . '/application/data/overrides/' . $overrides[$class];
        return true;
    }

    // Extract module namespace.
    $pos = strpos($class, '\\');
    if ($pos === false) {
        return false;
    }
    $moduleNamespace = substr($class, 0, $pos);

    // Check for conflict of a module that exists in both locations.
    $localModule = OMEKA_PATH . '/modules/' . $moduleNamespace;
    $addonModule = OMEKA_PATH . '/composer-addons/modules/' . $moduleNamespace;

    // Only fix if local exists as real dir and if addon exists.
    if (!is_dir($localModule) || is_link($localModule) || !is_dir($addonModule)) {
        return false;
    }

    // PSR-4: ModuleName\Foo\Bar -> modules/ModuleName/src/Foo/Bar.php.
    $relativePath = str_replace('\\', '/', substr($class, $pos + 1));
    $file = $localModule . '/src/' . $relativePath . '.php';

    if (file_exists($file)) {
        require_once $file;
        return true;
    }

    return false;
}, true, true);
