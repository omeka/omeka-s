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
            'Omeka\ActiveModules' => 'Omeka\Service\ActiveModulesFactory',
            'Omeka\Connection'    => 'Omeka\Service\ConnectionFactory',
        ),
        'invokables' => array(
            'ModuleRouteListener'         => 'Zend\Mvc\ModuleRouteListener',
            'Omeka\AuthorizationListener' => 'Omeka\Mvc\AuthorizationListener',
            'Omeka\Installation'          => 'Omeka\Installation\Installation',
        ),
    ),
    'listeners' => array(
        'ModuleRouteListener',
        'Omeka\AuthorizationListener',
    ),
    'installation_manager' => array(
        'tasks' => array(
            'Omeka\Installation\Task\CheckDbConfigurationTask',
            'Omeka\Installation\Task\InstallSchemaTask',
            'Omeka\Installation\Task\RecordMigrationsTask',
            'Omeka\Installation\Task\InstallDefaultVocabulariesTask',
            'Omeka\Installation\Task\CreateFirstUserTask',
        ),
    ),
    'migration_manager' => array(
        'path'      => OMEKA_PATH . '/data/migrations',
        'namespace' => 'Omeka\Db\Migrations',
        'entity'    => 'Omeka\Model\Entity\Migration',
    ),
    'connection' => $config,
);
