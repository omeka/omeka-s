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

try {
    Omeka\Mvc\Application::init(require 'application/config/application.config.php')->run();
} catch (\Exception $e) {
    http_response_code(500);
    error_log($e);
    include OMEKA_PATH . '/application/view-shared/error/fallback.phtml';
}
