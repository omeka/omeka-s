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
            'Omeka\Connection'          => 'Omeka\Service\ConnectionFactory',
            'Omeka\InstallationManager' => 'Omeka\Service\InstallationManagerFactory',
            'Omeka\MigrationManager'    => 'Omeka\Service\MigrationManagerFactory',
            'Omeka\ModuleManager'       => 'Omeka\Service\ModuleManagerFactory',
        ),
        'invokables' => array(
            'ModuleRouteListener'                => 'Zend\Mvc\ModuleRouteListener',
            'Omeka\ApiAuthenticationListener'    => 'Omeka\Mvc\ApiAuthenticationListener',
            'Omeka\AuthorizationListener'        => 'Omeka\Mvc\AuthorizationListener',
            'Omeka\InstallationRedirectListener' => 'Omeka\Mvc\InstallationRedirectListener',
            'Omeka\InstallationStatus'           => 'Omeka\Installation\InstallationStatus',
        ),
    ),
    'listeners' => array(
        'ModuleRouteListener',
        'Omeka\ApiAuthenticationListener',
        'Omeka\AuthorizationListener',
        'Omeka\InstallationRedirectListener'
    ),
    'installation_manager' => array(
        'tasks' => array(
            'Omeka\Installation\Task\CheckDbConfigurationTask',
            'Omeka\Installation\Task\InstallSchemaTask',
            'Omeka\Installation\Task\RecordMigrationsTask',
            'Omeka\Installation\Task\InstallDefaultVocabularyTask',
            'Omeka\Installation\Task\InstallDefaultVocabulariesTask',
            'Omeka\Installation\Task\CreateFirstUserTask',
        ),
    ),
    'connection' => $config,
);
