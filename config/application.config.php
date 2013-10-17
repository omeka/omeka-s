<?php
return array(
    'service_manager' => array(
        'factories' => array(
            'EntityManager' => 'Omeka\Service\EntityManagerFactory',
            'ApiManager' => 'Omeka\Service\ApiManagerFactory',
        ),
    ),
    'modules' => array(
        'Omeka',
    ),
    'module_listener_options' => array(
        'module_paths' => array(
            './module',
        ),
        'config_glob_paths' => array(
            'config/local.config.php'
        )
    ),
);
