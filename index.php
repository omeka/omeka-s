<?php
ini_set('display_errors', true);

require 'vendor/autoload.php';

Zend\Mvc\Application::init(require 'config/application.config.php')->run();
