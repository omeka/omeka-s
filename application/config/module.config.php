<?php
return array(
    'api_adapters' => array(
        'invokables' => array(
            'users'            => 'Omeka\Api\Adapter\Entity\UserAdapter',
            'vocabularies'     => 'Omeka\Api\Adapter\Entity\VocabularyAdapter',
            'resource_classes' => 'Omeka\Api\Adapter\Entity\ResourceClassAdapter',
            'resource_templates' => 'Omeka\Api\Adapter\Entity\ResourceTemplateAdapter',
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
            'Omeka\FilterManager'       => 'Omeka\Event\FilterManager',
            'Omeka\MvcExceptionListener'=> 'Omeka\Mvc\ExceptionListener',
            'Omeka\MvcListeners'        => 'Omeka\Mvc\MvcListeners',
            'Omeka\Settings'            => 'Omeka\Service\Settings',
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
        'Omeka\MvcExceptionListener',
        'Omeka\MvcListeners',
    ),
    'controllers' => array(
        'invokables' => array(
            'Omeka\Controller\Api'             => 'Omeka\Controller\ApiController',
            'Omeka\Controller\Install'         => 'Omeka\Controller\InstallController',
            'Omeka\Controller\Login'           => 'Omeka\Controller\LoginController',
            'Omeka\Controller\Maintenance'     => 'Omeka\Controller\MaintenanceController',
            'Omeka\Controller\Migrate'         => 'Omeka\Controller\MigrateController',
            'Omeka\Controller\Site\Index'      => 'Omeka\Controller\Site\IndexController',
            'Omeka\Controller\Admin\Index'     => 'Omeka\Controller\Admin\IndexController',
            'Omeka\Controller\Admin\Item'      => 'Omeka\Controller\Admin\ItemController',
            'Omeka\Controller\Admin\User'      => 'Omeka\Controller\Admin\UserController',
            'Omeka\Controller\Admin\Module'    => 'Omeka\Controller\Admin\ModuleController',
            'Omeka\Controller\Admin\ResourceTemplate' => 'Omeka\Controller\Admin\ResourceTemplateController',
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
            OMEKA_PATH . '/application/view',
        ),
        'strategies' => array(
            'Omeka\ViewApiJsonStrategy',
        ),
    ),
    'view_route_layouts' => array(
        'install' => 'layout/minimal',
        'migrate' => 'layout/minimal',
        'login'   => 'layout/minimal',
        'maintenance' => 'layout/minimal',
    ),
    'view_helpers' => array(
        'invokables' => array(
            'value'          => 'Omeka\View\Helper\Value',
            'htmlElement'    => 'Omeka\View\Helper\HtmlElement',
            'hyperlink'      => 'Omeka\View\Helper\Hyperlink',
            'messages'       => 'Omeka\View\Helper\Messages',
            'propertySelect' => 'Omeka\View\Helper\PropertySelect',
            'sortLink'       => 'Omeka\View\Helper\SortLink',
            'formElements'   => 'Omeka\View\Helper\FormElements',
            'propertySelector' => 'Omeka\View\Helper\PropertySelector',
            'resourceClassSelect' => 'Omeka\View\Helper\ResourceClassSelect',
        ),
    ),
    'media_types' => array(
        'img'     => 'Omeka\View\Helper\MediaType\Img',
        'youtube' => 'Omeka\View\Helper\MediaType\Youtube',
    ),
    'navigation' => array(
        'admin' => array(
            array(
                'label'      => 'Resources',
                'route'      => 'admin/default',
                'controller' => 'item',
                'resource'   => 'Omeka\Controller\Admin\Item',
                'class'      => 'resources',
                'pages' => array(
                    array(
                        'label'      => 'Item Sets',
                        'route'      => 'admin/default',
                        'controller' => 'item-set',
                        'action'     => 'browse',
                        'resource'   => 'Omeka\Controller\Admin\ItemSet',
                        'pages' => array(
                            array(
                                'route'      => 'admin/id',
                                'controller' => 'item-set',
                                'visible'    => false,
                            ),
                        ),
                    ),
                    array(
                        'label'      => 'Items',
                        'route'      => 'admin/default',
                        'controller' => 'item',
                        'action'     => 'browse',
                        'resource'   => 'Omeka\Controller\Admin\Item',
                        'pages' => array(
                            array(
                                'route'      => 'admin/id',
                                'controller' => 'item',
                                'visible'    => false,
                            ),
                        ),
                    ),
                    array(
                        'label'      => 'Media',
                        'route'      => 'admin/default',
                        'controller' => 'media',
                        'action'     => 'browse',
                        'resource'   => 'Omeka\Controller\Admin\Media',
                        'pages' => array(
                            array(
                                'route'      => 'admin/id',
                                'controller' => 'media',
                                'visible'    => false,
                            ),
                        ),
                    ),
                ),
            ),
            array(
                'label'      => 'Ontology',
                'route'      => 'admin/default',
                'controller' => 'vocabulary',
                'resource'   => 'Omeka\Controller\Admin\Vocabulary',
                'class'      => 'ontology',
                'pages'      => array(
                    array(
                        'label'      => 'Vocabularies',
                        'route'      => 'admin/default',
                        'controller' => 'vocabulary',
                        'action'     => 'browse',
                        'resource'   => 'Omeka\Controller\Admin\Vocabulary',
                        'pages' => array(
                            array(
                                'route'      => 'admin/id',
                                'controller' => 'vocabulary',
                                'visible'    => false,
                            ),
                        ),
                    ),
                    array(
                        'label'      => 'Import Vocabulary',
                        'route'      => 'admin/default',
                        'controller' => 'vocabulary',
                        'action'     => 'import',
                        'resource'   => 'Omeka\Controller\Admin\Vocabulary',
                    ),
                    array(
                        'label'      => 'Resource Templates',
                        'route'      => 'admin/default',
                        'controller' => 'resource-template',
                        'action'     => 'browse',
                        'resource'   => 'Omeka\Controller\Admin\ResourceTemplate',
                        'pages'      => array(
                            array(
                                'route'      => 'admin/id',
                                'controller' => 'resource-template',
                                'visible'    => false,
                            ),
                            array(
                                'route'      => 'admin/default',
                                'controller' => 'resource-template',
                                'visible'    => false,
                            ),
                        ),
                    ),
                ),
            ),
            array(
                'label'      => 'Users',
                'route'      => 'admin/default',
                'controller' => 'user',
                'action'     => 'browse',
                'resource'   => 'Omeka\Controller\Admin\User',
                'class'      => 'users',
                'pages' => array(
                    array(
                        'route'      => 'admin/id',
                        'controller' => 'user',
                        'visible'    => false,
                    ),
                ),
            ),
            array(
                'label'      => 'Modules',
                'route'      => 'admin/default',
                'controller' => 'module',
                'resource'   => 'Omeka\Controller\Admin\Module',
                'class'      => 'modules',
            ),
            array(
                'label'      => 'Sites',
                'route'      => 'admin/site',
                'controller' => 'site',
                'action'     => 'browse',
                'resource'   => 'Omeka\Controller\Admin\Site',
            ),
            array(
                'label'      => 'Settings',
                'route'      => 'admin/default',
                'controller' => 'setting',
                'action'     => 'browse',
                'resource'   => 'Omeka\Controller\Admin\Setting',
            ),

        ),
        'user' => array(
            array(
                'label'         => 'User Information',
                'route'         => 'admin/id',
                'action'        => 'edit',
                'useRouteMatch' => true,
            ),
            array(
                'label'         => 'Password',
                'route'         => 'admin/id',
                'action'        => 'change-password',
                'useRouteMatch' => true,
            ),
            array(
                'label'         => 'API Keys',
                'route'         => 'admin/id',
                'action'        => 'edit-keys',
                'useRouteMatch' => true,
            ),
        ),
    ),
    'entity_manager' => array(
        'is_dev_mode' => false,
        'mapping_classes_paths' => array(
            OMEKA_PATH . '/application/src/Model/Entity',
        ),
    ),
    'installation_manager' => array(
        'tasks' => array(
            'Omeka\Installation\Task\CheckEnvironmentTask',
            'Omeka\Installation\Task\ClearSessionTask',
            'Omeka\Installation\Task\CheckDbConfigurationTask',
            'Omeka\Installation\Task\InstallSchemaTask',
            'Omeka\Installation\Task\RecordMigrationsTask',
            'Omeka\Installation\Task\InstallDefaultVocabulariesTask',
            'Omeka\Installation\Task\CreateFirstUserTask',
            'Omeka\Installation\Task\AddDefaultSettingsTask',
        ),
    ),
    'translator' => array(
        'locale' => 'en_US',
        'translation_file_patterns' => array(
            array(
                'type'     => 'gettext',
                'base_dir' => OMEKA_PATH . '/application/language',
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
                    'route' => '/s/:site-slug',
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
                            'route' => '/:controller/:id[/[:action]]',
                            'constraints' => array(
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
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
                            'route' => '[/:resource[/:id]][/]',
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
            'maintenance' => array(
                'type' => 'Regex',
                'options' => array(
                    'regex' => '/maintenance(/.*)?',
                    'spec' => '/maintenance',
                    'defaults' => array(
                        'controller' => 'Omeka\Controller\Maintenance',
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
