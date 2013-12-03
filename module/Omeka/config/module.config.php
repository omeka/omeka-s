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
                'type'    => 'Segment',
                'options' => array(
                    'route' => '/[:site-slug]',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Omeka\Controller',
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
                ),
            ),
            'api' => array(
                'type' => 'Zend\Mvc\Router\Http\Segment',
                'options' => array(
                    'route' => '/api/:resource[/:id]',
                    'defaults' => array(
                        'controller' => 'Omeka\Controller\Api\Index',
                    ),
                ),
            ),
             'install' => array(
                'type' => 'Zend\Mvc\Router\Http\Segment',
                'options' => array(
                    'route' => '/install',
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
            'Omeka\Controller\Api\Index' => 'Omeka\Controller\Api\IndexController',
            'Omeka\Controller\Install'   => 'Omeka\Controller\InstallController',
            'Omeka\Controller\Index'     => 'Omeka\Controller\IndexController',
            'Omeka\Controller\Item'      => 'Omeka\Controller\ItemController',
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
