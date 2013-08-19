<?php
ini_set('display_errors', true);

require 'init_autoloader.php';

Zend\Mvc\Application::init(require 'config/application.config.php')->run();
