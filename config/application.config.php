<?php
$reader = new Zend\Config\Reader\Ini;
$config = $reader->fromFile(OMEKA_PATH . '/config/database.ini');
return [
    'modules' => [
        'Omeka',
    ],
    'module_listener_options' => [
        'module_paths' => [
            'Omeka' => OMEKA_PATH . '/application',
            OMEKA_PATH . '/modules',
        ],
        'config_glob_paths' => [
            OMEKA_PATH . '/config/local.config.php'
        ]
    ],
    'service_manager' => [
        'factories' => [
            'Omeka\Connection'    => 'Omeka\Service\ConnectionFactory',
            'Omeka\ModuleManager' => 'Omeka\Service\ModuleManagerFactory',
        ],
        'invokables' => [
            'Omeka\Status' => 'Omeka\Mvc\Status',
        ],
    ],
    'connection' => $config,
];
