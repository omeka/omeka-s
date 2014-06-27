<?php
$reader = new Zend\Config\Reader\Ini;
$config = $reader->fromFile(OMEKA_PATH . '/config/database.ini');
return array(
    'modules' => array(
        'Omeka',
    ),
    'module_listener_options' => array(
        'module_paths' => array(
            OMEKA_PATH . '/application',
            OMEKA_PATH . '/module',
        ),
        'config_glob_paths' => array(
            OMEKA_PATH . '/config/local.config.php'
        )
    ),
    'service_manager' => array(
        'factories' => array(
            'Omeka\Connection'    => 'Omeka\Service\ConnectionFactory',
            'Omeka\ModuleManager' => 'Omeka\Service\ModuleManagerFactory',
        ),
        'invokables' => array(
            'Omeka\Status' => 'Omeka\Mvc\Status',
        ),
    ),
    'connection' => $config,
);
