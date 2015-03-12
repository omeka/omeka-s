<?php
return array(
    'listeners' => array(
        'ModuleRouteListener',
        'Omeka\MvcExceptionListener',
        'Omeka\MvcListeners',
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
        'install'     => 'layout/minimal',
        'migrate'     => 'layout/minimal',
        'login'       => 'layout/minimal',
        'maintenance' => 'layout/minimal',
    ),
    'temp_dir' => sys_get_temp_dir(),
    'entity_manager' => array(
        'is_dev_mode' => false,
        'mapping_classes_paths' => array(
            OMEKA_PATH . '/application/src/Model/Entity',
        ),
        'resource_discriminator_map' => array(
            'Omeka\Model\Entity\Item'    => 'Omeka\Model\Entity\Item',
            'Omeka\Model\Entity\Media'   => 'Omeka\Model\Entity\Media',
            'Omeka\Model\Entity\ItemSet' => 'Omeka\Model\Entity\ItemSet',
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
                'type'        => 'gettext',
                'base_dir'    => OMEKA_PATH . '/application/language',
                'pattern'     => '%s.mo',
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
    'jobs' => array(
        'dispatch_strategy' => 'Omeka\Job\Strategy\PhpCliStrategy',
        'phpcli_path'       => null,
    ),
    'http_client' => array(
        'adapter'   => 'Zend\Http\Client\Adapter\Socket',
        'sslcapath' => '/etc/ssl/certs',
    ),
    'service_manager' => array(
        'factories' => array(
            'Navigation'                  => 'Zend\Navigation\Service\DefaultNavigationFactory',
            'Omeka\Acl'                   => 'Omeka\Service\AclFactory',
            'Omeka\ApiAdapterManager'     => 'Omeka\Service\ApiAdapterManagerFactory',
            'Omeka\AuthenticationService' => 'Omeka\Service\AuthenticationServiceFactory',
            'Omeka\EntityManager'         => 'Omeka\Service\EntityManagerFactory',
            'Omeka\FileRendererManager'   => 'Omeka\Service\FileRendererManagerFactory',
            'Omeka\FileStore\Local'       => 'Omeka\Service\FileStoreLocalFactory',
            'Omeka\InstallationManager'   => 'Omeka\Service\InstallationManagerFactory',
            'Omeka\Logger'                => 'Omeka\Service\LoggerFactory',
            'Omeka\MediaHandlerManager'   => 'Omeka\Service\MediaHandlerManagerFactory',
            'Omeka\MigrationManager'      => 'Omeka\Service\MigrationManagerFactory',
            'Omeka\ViewApiJsonStrategy'   => 'Omeka\Service\ViewApiJsonStrategyFactory',
            'Omeka\JobDispatcher'         => 'Omeka\Service\JobDispatcherFactory',
            'Omeka\HttpClient'            => 'Omeka\Service\HttpClientFactory',
            'Omeka\MediaTypeExtensionMap' => 'Omeka\Service\MediaTypeExtensionMapFactory',
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
            'Omeka\FileStore' => 'Omeka\FileStore\Local',
            'Zend\Authentication\AuthenticationService' => 'Omeka\AuthenticationService'
        ),
        'shared' => array(
            'Omeka\Paginator' => false,
            'Omeka\HttpClient' => false,
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            'Omeka\Controller\Api'                    => 'Omeka\Controller\ApiController',
            'Omeka\Controller\Install'                => 'Omeka\Controller\InstallController',
            'Omeka\Controller\Login'                  => 'Omeka\Controller\LoginController',
            'Omeka\Controller\Maintenance'            => 'Omeka\Controller\MaintenanceController',
            'Omeka\Controller\Migrate'                => 'Omeka\Controller\MigrateController',
            'Omeka\Controller\Site\Index'             => 'Omeka\Controller\Site\IndexController',
            'Omeka\Controller\Admin\Index'            => 'Omeka\Controller\Admin\IndexController',
            'Omeka\Controller\Admin\Item'             => 'Omeka\Controller\Admin\ItemController',
            'Omeka\Controller\Admin\ItemSet'          => 'Omeka\Controller\Admin\ItemSetController',
            'Omeka\Controller\Admin\User'             => 'Omeka\Controller\Admin\UserController',
            'Omeka\Controller\Admin\Module'           => 'Omeka\Controller\Admin\ModuleController',
            'Omeka\Controller\Admin\Job'              => 'Omeka\Controller\Admin\JobController',
            'Omeka\Controller\Admin\ResourceTemplate' => 'Omeka\Controller\Admin\ResourceTemplateController',
            'Omeka\Controller\Admin\Vocabulary'       => 'Omeka\Controller\Admin\VocabularyController',
            'Omeka\Controller\Admin\Property'         => 'Omeka\Controller\Admin\PropertyController',
            'Omeka\Controller\Admin\ResourceClass'    => 'Omeka\Controller\Admin\ResourceClassController',
            'Omeka\Controller\SiteAdmin\Index'        => 'Omeka\Controller\SiteAdmin\IndexController',
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
    'api_adapters' => array(
        'invokables' => array(
            'users'              => 'Omeka\Api\Adapter\Entity\UserAdapter',
            'vocabularies'       => 'Omeka\Api\Adapter\Entity\VocabularyAdapter',
            'resource_classes'   => 'Omeka\Api\Adapter\Entity\ResourceClassAdapter',
            'resource_templates' => 'Omeka\Api\Adapter\Entity\ResourceTemplateAdapter',
            'properties'         => 'Omeka\Api\Adapter\Entity\PropertyAdapter',
            'values'             => 'Omeka\Api\Adapter\Entity\ValueAdapter',
            'items'              => 'Omeka\Api\Adapter\Entity\ItemAdapter',
            'media'              => 'Omeka\Api\Adapter\Entity\MediaAdapter',
            'item_sets'          => 'Omeka\Api\Adapter\Entity\ItemSetAdapter',
            'modules'            => 'Omeka\Api\Adapter\ModuleAdapter',
            'sites'              => 'Omeka\Api\Adapter\Entity\SiteAdapter',
            'jobs'               => 'Omeka\Api\Adapter\Entity\JobAdapter',
        ),
    ),
    'view_helpers' => array(
        'invokables' => array(
            'htmlElement'            => 'Omeka\View\Helper\HtmlElement',
            'hyperlink'              => 'Omeka\View\Helper\Hyperlink',
            'messages'               => 'Omeka\View\Helper\Messages',
            'propertySelect'         => 'Omeka\View\Helper\PropertySelect',
            'sortLink'               => 'Omeka\View\Helper\SortLink',
            'formElements'           => 'Omeka\View\Helper\FormElements',
            'propertySelector'       => 'Omeka\View\Helper\PropertySelector',
            'formPropertyInputs'     => 'Omeka\View\Helper\PropertyInputs',
            'resourceClassSelect'    => 'Omeka\View\Helper\ResourceClassSelect',
            'resourceTemplateSelect' => 'Omeka\View\Helper\ResourceTemplateSelect'
        ),
    ),
    'media_handlers' => array(
        'invokables' => array(
            'oembed'  => 'Omeka\Media\Handler\OEmbedHandler',
            'url'     => 'Omeka\Media\Handler\UrlHandler',
            'upload'  => 'Omeka\Media\Handler\UploadHandler',
            'youtube' => 'Omeka\Media\Handler\YoutubeHandler',
        ),
    ),
    'file_renderers' => array(
        'invokables' => array(
            'image' => 'Omeka\Media\FileRenderer\ImageRenderer',
        ),
        'aliases' => array(
            'image/png'  => 'image',
            'image/jpeg' => 'image',
            'image/gif'  => 'image',
        ),
    ),
);
