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
    'permissions' => array(
        'acl_resources' => array(
            'Omeka\ModuleManager',
        ),
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
    'installer' => array(
        'tasks' => array(
            'Omeka\Installation\Task\CheckEnvironmentTask',
            'Omeka\Installation\Task\CheckDirPermissionsTask',
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
    'http_client' => array(
        'adapter'   => 'Zend\Http\Client\Adapter\Socket',
        'sslcapath' => '/etc/ssl/certs',
    ),
    'cli' => array(
        'execute_strategy' => 'exec',
        'phpcli_path' => null,
    ),
    'file_manager' => array(
        'store' => 'Omeka\File\LocalStore',
        'thumbnailer' => 'Omeka\File\ImageMagickThumbnailer',
        'thumbnail_types' => array(
            'large' => array(
                'strategy' => 'default',
                'constraint' => 800,
                'options' => array(),
            ),
            'medium' => array(
                'strategy' => 'default',
                'constraint' => 200,
                'options' => array(),
            ),
            'square' => array(
                'strategy' => 'square',
                'constraint' => 200,
                'options' => array(
                    'gravity' => 'center',
                ),
            ),
        ),
        'thumbnail_options' => array(
            'imagemagick_dir' => null,
            'page' => 0,
        ),
        'thumbnail_fallbacks' => array(
            'default' => array('thumbnails/default.png', 'Omeka'),
            'fallbacks' => array(
                'image' => array('thumbnails/image.png', 'Omeka'),
                'video' => array('thumbnails/video.png', 'Omeka'),
                'audio' => array('thumbnails/audio.png', 'Omeka'),
            ),
        ),
    ),
    'service_manager' => array(
        'abstract_factories' => array(
            'Zend\Navigation\Service\NavigationAbstractServiceFactory',
        ),
        'factories' => array(
            'Omeka\Acl'                   => 'Omeka\Service\AclFactory',
            'Omeka\ApiAdapterManager'     => 'Omeka\Service\ApiAdapterManagerFactory',
            'Omeka\AuthenticationService' => 'Omeka\Service\AuthenticationServiceFactory',
            'Omeka\EntityManager'         => 'Omeka\Service\EntityManagerFactory',
            'Omeka\FileRendererManager'   => 'Omeka\Service\FileRendererManagerFactory',
            'Omeka\Installer'             => 'Omeka\Service\InstallerFactory',
            'Omeka\Logger'                => 'Omeka\Service\LoggerFactory',
            'Omeka\MediaHandlerManager'   => 'Omeka\Service\MediaHandlerManagerFactory',
            'Omeka\MigrationManager'      => 'Omeka\Service\MigrationManagerFactory',
            'Omeka\ViewApiJsonStrategy'   => 'Omeka\Service\ViewApiJsonStrategyFactory',
            'Omeka\JobDispatcher'         => 'Omeka\Service\JobDispatcherFactory',
            'Omeka\HttpClient'            => 'Omeka\Service\HttpClientFactory',
            'Omeka\File\LocalStore'       => 'Omeka\Service\LocalStoreFactory',
            'Omeka\File\MediaTypeMap'     => 'Omeka\Service\MediaTypeMapFactory',
            'Omeka\File\Manager'          => 'Omeka\Service\FileManagerFactory',
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
            'Omeka\File'                => 'Omeka\File\File',
            'Omeka\Cli'                 => 'Omeka\Service\Cli',
            'Omeka\File\GdThumbnailer'          => 'Omeka\File\Thumbnailer\GdThumbnailer',
            'Omeka\File\ImagickThumbnailer'     => 'Omeka\File\Thumbnailer\ImagickThumbnailer',
            'Omeka\File\ImageMagickThumbnailer' => 'Omeka\File\Thumbnailer\ImageMagickThumbnailer',
            'Omeka\JobDispatchStrategy\PhpCli'      => 'Omeka\Job\Strategy\PhpCliStrategy',
            'Omeka\JobDispatchStrategy\Synchronous' => 'Omeka\Job\Strategy\SynchronousStrategy',
        ),
        'aliases' => array(
            'Omeka\JobDispatchStrategy' => 'Omeka\JobDispatchStrategy\PhpCli',
            'Zend\Authentication\AuthenticationService' => 'Omeka\AuthenticationService'
        ),
        'shared' => array(
            'Omeka\Paginator' => false,
            'Omeka\HttpClient' => false,
            'Omeka\File\GdThumbnailer' => false,
            'Omeka\File' => false,
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
            'Omeka\Controller\Admin\Media'            => 'Omeka\Controller\Admin\MediaController',
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
            'formField'              => 'Omeka\View\Helper\FormField',
            'propertySelector'       => 'Omeka\View\Helper\PropertySelector',
            'formPropertyInputs'     => 'Omeka\View\Helper\PropertyInputs',
            'resourceClassSelect'    => 'Omeka\View\Helper\ResourceClassSelect',
        ),
    ),
    'media_handlers' => array(
        'invokables' => array(
            'upload'  => 'Omeka\Media\Handler\UploadHandler',
            'url'     => 'Omeka\Media\Handler\UrlHandler',
            'oembed'  => 'Omeka\Media\Handler\OEmbedHandler',
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
