<?php
return array(
    'api_adapters' => array(
        'invokables' => array(
            'users'            => 'Omeka\Api\Adapter\Entity\UserAdapter',
            'vocabularies'     => 'Omeka\Api\Adapter\Entity\VocabularyAdapter',
            'resource_classes' => 'Omeka\Api\Adapter\Entity\ResourceClassAdapter',
            'properties'       => 'Omeka\Api\Adapter\Entity\PropertyAdapter',
            'values'           => 'Omeka\Api\Adapter\Entity\ValueAdapter',
            'items'            => 'Omeka\Api\Adapter\Entity\ItemAdapter',
            'media'            => 'Omeka\Api\Adapter\Entity\MediaAdapter',
            'item_sets'        => 'Omeka\Api\Adapter\Entity\ItemSetAdapter',
            'modules'          => 'Omeka\Api\Adapter\ModuleAdapter',
        ),
    ),
    'service_manager' => array(
        'factories' => array(
            'Navigation'                  => 'Zend\Navigation\Service\DefaultNavigationFactory',
            'Omeka\Acl'                   => 'Omeka\Service\AclFactory',
            'Omeka\ApiAdapterManager'     => 'Omeka\Service\ApiAdapterManagerFactory',
            'Omeka\AuthenticationService' => 'Omeka\Service\AuthenticationServiceFactory',
            'Omeka\EntityManager'         => 'Omeka\Service\EntityManagerFactory',
            'Omeka\InstallationManager'   => 'Omeka\Service\InstallationManagerFactory',
            'Omeka\Logger'                => 'Omeka\Service\LoggerFactory',
            'Omeka\MigrationManager'      => 'Omeka\Service\MigrationManagerFactory',
            'Omeka\ViewApiJsonStrategy'   => 'Omeka\Service\ViewApiJsonStrategyFactory',
        ),
        'invokables' => array(
            'ModuleRouteListener'       => 'Zend\Mvc\ModuleRouteListener',
            'Omeka\ApiManager'          => 'Omeka\Api\Manager',
            'Omeka\DbHelper'            => 'Omeka\Db\Helper',
            'Omeka\FilterManager'       => 'Omeka\Event\FilterManager',
            'Omeka\MvcListeners'        => 'Omeka\Mvc\MvcListeners',
            'Omeka\Options'             => 'Omeka\Service\Options',
            'Omeka\Paginator'           => 'Omeka\Service\Paginator',
            'Omeka\RdfImporter'         => 'Omeka\Service\RdfImporter',
            'Omeka\ViewApiJsonRenderer' => 'Omeka\View\Renderer\ApiJsonRenderer',
        ),
        'aliases' => array(
            'Zend\Authentication\AuthenticationService' => 'Omeka\AuthenticationService'
        ),
        'shared' => array(
            'Omeka\Paginator' => false,
        ),
    ),
    'listeners' => array(
        'ModuleRouteListener',
        'Omeka\MvcListeners',
    ),
    'controllers' => array(
        'invokables' => array(
            'Omeka\Controller\Api'             => 'Omeka\Controller\ApiController',
            'Omeka\Controller\Install'         => 'Omeka\Controller\InstallController',
            'Omeka\Controller\Login'           => 'Omeka\Controller\LoginController',
            'Omeka\Controller\Migrate'         => 'Omeka\Controller\MigrateController',
            'Omeka\Controller\Site\Index'      => 'Omeka\Controller\Site\IndexController',
            'Omeka\Controller\Admin\Index'     => 'Omeka\Controller\Admin\IndexController',
            'Omeka\Controller\Admin\Item'      => 'Omeka\Controller\Admin\ItemController',
            'Omeka\Controller\Admin\User'      => 'Omeka\Controller\Admin\UserController',
            'Omeka\Controller\Admin\Module'    => 'Omeka\Controller\Admin\ModuleController',
            'Omeka\Controller\Admin\Vocabulary' => 'Omeka\Controller\Admin\VocabularyController',
            'Omeka\Controller\SiteAdmin\Index' => 'Omeka\Controller\SiteAdmin\IndexController',
        ),
    ),
    'controller_plugins' => array(
        'invokables' => array(
            'api'       => 'Omeka\Mvc\Controller\Plugin\Api',
            'translate' => 'Omeka\Mvc\Controller\Plugin\Translate',
            'messenger' => 'Omeka\Mvc\Controller\Plugin\Messenger',
            'paginator' => 'Omeka\Mvc\Controller\Plugin\Paginator',
        ),
    ),
    'view_manager' => array(
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_path_stack'      => array(
            OMEKA_PATH . '/application/Omeka/view',
        ),
        'strategies' => array(
            'Omeka\ViewApiJsonStrategy',
        ),
    ),
    'view_route_layouts' => array(
        'install' => 'layout/minimal',
        'migrate' => 'layout/minimal',
        'login'   => 'layout/minimal',
    ),
    'view_helpers' => array(
        'invokables' => array(
            'value'       => 'Omeka\View\Helper\Value',
            'htmlElement' => 'Omeka\View\Helper\HtmlElement',
            'messages'    => 'Omeka\View\Helper\Messages',
        ),
    ),
    'media_types' => array(
        'img'     => 'Omeka\View\Helper\MediaType\Img',
        'youtube' => 'Omeka\View\Helper\MediaType\Youtube',
    ),
    'navigation' => array(
        'default' => array(
            array(
                'label'      => 'Items',
                'class'      => 'fa-items',
                'route'      => 'admin/default',
                'controller' => 'item',
                'action'     => 'browse',
                'resource'   => 'Omeka\Controller\Admin\Item',
            ),
            array(
                'label'      => 'Item Sets',
                'class'      => 'fa-item-set',
                'route'      => 'admin/default',
                'controller' => 'item-set',
                'action'     => 'browse',
                'resource'   => 'Omeka\Controller\Admin\ItemSet',
            ),
            array(
                'label'      => 'Vocabularies',
                'class'      => 'fa-vocab',
                'route'      => 'admin/default',
                'controller' => 'vocabulary',
                'action'     => 'browse',
                'resource'   => 'Omeka\Controller\Admin\Vocabulary',
            ),
            array(
                'label'      => 'Modules',
                'class'      => 'fa-module',
                'route'      => 'admin/default',
                'controller' => 'module',
                'action'     => 'browse',
                'resource'   => 'Omeka\Controller\Admin\Module',
            ),
            array(
                'label'      => 'Users',
                'class'      => 'fa-users',
                'route'      => 'admin/default',
                'controller' => 'user',
                'action'     => 'browse',
                'resource'   => 'Omeka\Controller\Admin\User',
            ),
            array(
                'label'      => 'Sites',
                'class'      => 'fa-site',
                'route'      => 'admin/site',
                'controller' => 'site',
                'action'     => 'browse',
                'resource'   => 'Omeka\Controller\Admin\Site',
            ),
            array(
                'label'      => 'Settings',
                'class'      => 'fa-settings',
                'route'      => 'admin/default',
                'controller' => 'setting',
                'action'     => 'browse',
                'resource'   => 'Omeka\Controller\Admin\Setting',
            ),
        ),
    ),
    'entity_manager' => array(
        'is_dev_mode' => false,
        'mapping_classes_paths' => array(
            OMEKA_PATH . '/application/Omeka/src/Omeka/Model/Entity',
        ),
    ),
    'installation_manager' => array(
        'tasks' => array(
            'Omeka\Installation\Task\ClearSessionTask',
            'Omeka\Installation\Task\CheckDbConfigurationTask',
            'Omeka\Installation\Task\InstallSchemaTask',
            'Omeka\Installation\Task\RecordMigrationsTask',
            'Omeka\Installation\Task\InstallDefaultVocabularyTask',
            'Omeka\Installation\Task\InstallDefaultVocabulariesTask',
            'Omeka\Installation\Task\CreateFirstUserTask',
            'Omeka\Installation\Task\AddDefaultOptionsTask',
        ),
    ),
    'translator' => array(
        'locale' => 'en_US',
        'translation_file_patterns' => array(
            array(
                'type'     => 'gettext',
                'base_dir' => OMEKA_PATH . '/application/Omeka/language',
                'pattern'  => '%s.mo',
                'text_domain' => null,
            ),
        ),
    ),
    'loggers' => array(
        'application' => array(
            'log'  => false,
            'path' => OMEKA_PATH . '/data/logs/application.log',
        ),
        'sql' => array(
            'log'  => false,
            'path' => OMEKA_PATH . '/data/logs/sql.log',
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
                            'route' => '/[:controller[/:action][/]]',
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
                            'route' => '/[:controller[/:action][/[:id]]]',
                            'constraints' => array(
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'id'         => '\d+',
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
                                        'action' => 'show',
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
            'migrate' => array(
                'type' => 'Regex',
                'options' => array(
                    'regex' => '/migrate(/.*)?',
                    'spec' => '/migrate',
                    'defaults' => array(
                        'controller' => 'Omeka\Controller\Migrate',
                        'action' => 'index',
                     ),
                ),
            ),
            'login' => array(
                'type' => 'Regex',
                'options' => array(
                    'regex' => '/login(/.*)?',
                    'spec' => '/login',
                    'defaults' => array(
                        'controller' => 'Omeka\Controller\Login',
                        'action' => 'login',
                     ),
                ),
            ),
            'logout' => array(
                'type' => 'Regex',
                'options' => array(
                    'regex' => '/logout(/.*)?',
                    'spec' => '/logout',
                    'defaults' => array(
                        'controller' => 'Omeka\Controller\Login',
                        'action' => 'logout',
                     ),
                ),
            ),
        ),
    ),
);
