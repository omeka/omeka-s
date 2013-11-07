<?php
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
);
