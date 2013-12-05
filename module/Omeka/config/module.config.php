<?php
return array(
    'service_manager' => array(
        'factories' => array(
            'ApiManager'          => 'Omeka\Service\ApiManagerFactory',
            'EntityManager'       => 'Omeka\Service\EntityManagerFactory',
            'InstallationManager' => 'Omeka\Service\InstallationManagerFactory',
            'Logger'              => 'Omeka\Service\LoggerFactory',
            'ViewApiJsonStrategy' => 'Omeka\Service\ViewApiJsonStrategyFactory',
            
        ),
        'invokables' => array(
            'ViewApiJsonRenderer' => 'Omeka\View\Renderer\ApiJsonRenderer',
        ),
    ),
    'router' => array(
        'routes' => array(
            'site' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/:site-slug',
                    'constraints' => array(
                        'site-slug'  => '[a-zA-Z0-9_-]+',
                    ),
                    'defaults' => array(
                        '__NAMESPACE__' => 'Omeka\Controller\Site',
                        'controller'    => 'Index',
                        'action'        => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'default' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/[:controller[/:action]]',
                            'constraints' => array(
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                        ),
                    ),
                    'id' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/:controller/:id',
                            'constraints' => array(
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'id'         => '\d+',
                            ),
                            'defaults' => array(
                                'action' => 'show',
                            ),
                        ),
                    ),
                ),
            ),
            'admin' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/admin',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Omeka\Controller\Admin',
                        'controller'    => 'Index',
                        'action'        => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'default' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/[:controller[/:action]]',
                            'constraints' => array(
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                        ),
                    ),
                    'id' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/:controller/:id',
                            'constraints' => array(
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'id'         => '\d+',
                            ),
                            'defaults' => array(
                                'action' => 'edit',
                            ),
                        ),
                    ),
                    'site' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route' => '/manage',
                            'defaults' => array(
                                '__NAMESPACE__' => 'Omeka\Controller\SiteAdmin',
                                'controller'    => 'Index',
                                'action'        => 'index',
                            ),
                        ),
                        'may_terminate' => true,
                        'child_routes' => array(
                            'default' => array(
                                'type' => 'Segment',
                                'options' => array(
                                    'route' => '/[:site-slug[/:controller[/:action]]]',
                                    'constraints' => array(
                                        'site-slug'  => '[a-zA-Z0-9_-]+',
                                        'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                        'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                                    ),
                                ),
                            ),
                            'id' => array(
                                'type' => 'Segment',
                                'options' => array(
                                    'route' => '/:site-slug/:controller/:id',
                                    'constraints' => array(
                                        'site-slug'  => '[a-zA-Z0-9_-]+',
                                        'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                        'id'         => '\d+',
                                    ),
                                    'defaults' => array(
                                        'action' => 'edit',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            'api' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/api',
                    'defaults' => array(
                        'controller' => 'Omeka\Controller\Api',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'default' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/[:resource[/:id]]',
                            'constraints' => array(
                                'resource' => '[a-zA-Z0-9_-]+',
                            ),
                        ),
                    ),
                ),
            ),
            'install' => array(
                'type' => 'Regex',
                'options' => array(
                    'regex' => '/install(/.*)?',
                    'spec' => '/install',
                    'defaults' => array(
                        'controller' => 'Omeka\Controller\Install',
                        'action' => 'index',
                     ),
                ),
            ),
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            'Omeka\Controller\Api' => 'Omeka\Controller\ApiController',
            'Omeka\Controller\Install' => 'Omeka\Controller\InstallController',
            'Omeka\Controller\Site\Index' => 'Omeka\Controller\Site\IndexController',
            'Omeka\Controller\Admin\Index' => 'Omeka\Controller\Admin\IndexController',
            'Omeka\Controller\Admin\Item' => 'Omeka\Controller\Admin\ItemController',
            'Omeka\Controller\SiteAdmin\Index' => 'Omeka\Controller\SiteAdmin\IndexController',
        ),
    ),
    'view_manager' => array(
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_path_stack'      => array(
            OMEKA_PATH . '/module/Omeka/view',
        ),
        'strategies' => array(
            'ViewApiJsonStrategy',
        ),
    ),
    'api_manager' => array(
        'resources' => array(
            'users' => array(
                'adapter_class' => 'Omeka\Api\Adapter\Entity\UserAdapter',
            ),
            'vocabularies' => array(
                'adapter_class' => 'Omeka\Api\Adapter\Entity\VocabularyAdapter',
            ),
            'resource_classes' => array(
                'adapter_class' => 'Omeka\Api\Adapter\Entity\ResourceClassAdapter',
            ),
            'properties' => array(
                'adapter_class' => 'Omeka\Api\Adapter\Entity\PropertyAdapter',
            ),
            'values' => array(
                'adapter_class' => 'Omeka\Api\Adapter\Entity\ValueAdapter',
            ),
            'items' => array(
                'adapter_class' => 'Omeka\Api\Adapter\Entity\ItemAdapter',
            ),
            'rdf_vocabulary' => array(
                'adapter_class' => 'Omeka\Api\Adapter\RdfVocabularyAdapter',
            ),
        ),
    ),
    'entity_manager' => array(
        'conn' => array(
            'user'        => null,
            'password'    => null,
            'dbname'      => null,
            'host'        => null,
            'port'        => null,
            'unix_socket' => null,
            'charset'     => 'utf8',
            'driver'      => 'pdo_mysql',
        ),
        'table_prefix' => 'omeka_',
        'is_dev_mode'  => false,
    ),
    'loggers' => array(
        'application' => array(
            'log' => false,
            'path' => OMEKA_PATH . '/data/logs/application.log',
        ),
        'sql' => array(
            'log' => false,
            'path' => OMEKA_PATH . '/data/logs/sql.log',
        ),
    ),
    'installation_manager' => array(
        'tasks' => array(
            'Omeka\Installation\Task\CheckDbConfigurationTask',
            'Omeka\Installation\Task\InstallSchemaTask',
            'Omeka\Installation\Task\InstallDefaultVocabulariesTask',
        ),
    ),
);
