<?php
namespace Omeka;

$reader = new \Laminas\Config\Reader\Ini;
return [
    'modules' => [
        'Laminas\Form',
        'Laminas\I18n',
        'Laminas\Mvc\I18n',
        'Laminas\Mvc\Plugin\Identity',
        'Laminas\Navigation',
        'Laminas\Router',
        'Laminas\ZendFrameworkBridge',
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
