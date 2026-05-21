<?php
define('OMEKA_PATH', __DIR__);
chdir(OMEKA_PATH);
date_default_timezone_set('UTC');

require 'vendor/autoload.php';

spl_autoload_register(static function ($class) {
    $overrides = [
        'Doctrine\Common\Proxy\AbstractProxyFactory' => 'AbstractProxyFactory.php',
        'Doctrine\ORM\Proxy\ProxyFactory' => 'ProxyFactory.php',
    ];
    if (isset($overrides[$class])) {
        require OMEKA_PATH . '/application/data/overrides/' . $overrides[$class];
    }
}, true, true);
