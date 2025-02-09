<?php
namespace Omeka\Db;

use Doctrine\Common\Proxy\Autoloader;

class ProxyAutoloader
{
    public static function register($proxyDirs, $proxyNamespace)
    {
        $proxyNamespace = ltrim($proxyNamespace, '\\');

        $autoloader = function ($className) use ($proxyDirs, $proxyNamespace) {
            if (0 === strpos($className, $proxyNamespace)) {
                foreach ($proxyDirs as $proxyDir) {
                    $file = Autoloader::resolveFile($proxyDir, $proxyNamespace, $className);

                    if (file_exists($file)) {
                        require $file;
                        break;
                    }
                }
            }
        };

        spl_autoload_register($autoloader);
        return $autoloader;
    }
}
