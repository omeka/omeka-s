<?php
error_reporting(E_ALL);
if ((isset($_SERVER['APPLICATION_ENV'])
        && 'development' == $_SERVER['APPLICATION_ENV'])
    ||
    (isset($_SERVER['REDIRECT_APPLICATION_ENV'])
        && 'development' == $_SERVER['REDIRECT_APPLICATION_ENV'])
) {
    ini_set('display_errors', 1);
} else {
    ini_set('display_errors', 0);
}

require 'bootstrap.php';

Omeka\Mvc\Application::init(require 'config/application.config.php')->run();
