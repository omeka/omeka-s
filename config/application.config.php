<?php
$reader = new Zend\Config\Reader\Ini;
$config = $reader->fromFile(OMEKA_PATH . '/config/database.ini');
return array(
    'modules' => array(
        'Omeka',
    ),
    'module_listener_options' => array(
        'module_paths' => array(
            OMEKA_PATH . '/module',
        ),
        'config_glob_paths' => array(
            OMEKA_PATH . '/config/local.config.php'
        )
    ),
    'service_manager' => array(
        'factories' => array(
            'Connection' => 'Omeka\Service\ConnectionFactory',
        )
    ),
    'connection' => $config,
);
