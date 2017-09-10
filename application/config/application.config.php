<?php
namespace Omeka;

$reader = new \Zend\Config\Reader\Ini;
return [
    'modules' => [
        'Zend\Form',
        'Zend\I18n',
        'Zend\Mvc\I18n',
        'Zend\Mvc\Plugin\Identity',
        'Zend\Navigation',
        'Zend\Router',
        'Omeka',
    ],
    'module_listener_options' => [
        'module_paths' => [
            'Omeka' => OMEKA_PATH . '/application',
            OMEKA_PATH . '/modules',
        ],
        'config_glob_paths' => [
            OMEKA_PATH . '/config/local.config.php',
        ],
    ],
    'service_manager' => [
        'factories' => [
            'Omeka\Connection' => Service\ConnectionFactory::class,
            'Omeka\ModuleManager' => Service\ModuleManagerFactory::class,
            'Omeka\Status' => Service\StatusFactory::class,
        ],
    ],
    'connection' => $reader->fromFile(OMEKA_PATH . '/config/database.ini'),
];
