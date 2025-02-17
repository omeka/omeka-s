<?php

namespace EADImport;

return [
    'entity_manager' => [
        'mapping_classes_paths' => [
            dirname(__DIR__) . '/src/Entity',
        ],
        'proxy_paths' => [
            dirname(__DIR__) . '/data/doctrine-proxies',
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view',
        ],
    ],
    'view_helpers' => [
        'invokables' => [
            'PropertyLoader' => View\Helper\PropertyLoader::class,
            'MappingLoader' => View\Helper\MappingLoader::class,
        ],
    ],
    'api_adapters' => [
        'invokables' => [
            'eadimport_entities' => Api\Adapter\EntityAdapter::class,
            'eadimport_imports' => Api\Adapter\ImportAdapter::class,
            'eadimport_mapping_models' => Api\Adapter\MappingModelAdapter::class,
        ],
    ],
    'controllers' => [
        'factories' => [
            'EADImport\Controller\Admin\Index' => Service\Controller\Admin\IndexControllerFactory::class,
            'EADImport\Controller\Admin\MappingModel' => Service\Controller\Admin\MappingModelControllerFactory::class,
        ],
    ],
    'form_elements' => [
        'invokables' => [
            'EADImport\Form\LoadForm' => Form\LoadForm::class,
            'EADImport\Form\MappingForm' => Form\MappingForm::class,
            'EADImport\Form\MappingModelSaverForm' => Form\MappingModelSaverForm::class,
            'EADImport\Form\MappingModelEditForm' => Form\MappingModelEditForm::class,
        ],
    ],
    'navigation' => [
        'AdminModule' => [
            [
                'label' => 'Import EAD',
                'route' => 'admin/eadimport',
                'resource' => 'EADImport\Controller\Admin\Index',
                'pages' => [
                    [
                        'label' => 'Load from file',
                        'route' => 'admin/eadimport',
                        'resource' => 'EADImport\Controller\Admin\Index',
                    ],
                    [
                        'label' => 'Import',
                        'route' => 'admin/eadimport/map',
                        'resource' => 'EADImport\Controller\Admin\Index',
                        'visible' => false,
                    ],
                    [
                        'label' => 'Past imports',
                        'route' => 'admin/eadimport/past-imports',
                        'resource' => 'EADImport\Controller\Admin\Index',
                    ],
                    [
                        'label' => 'Mapping models',
                        'route' => 'admin/eadimport/mapping-model',
                        'resource' => 'EADImport\Controller\Admin\MappingModel',
                    ],
                ],
            ],
        ],
    ],
    'router' => [
        'routes' => [
            'admin' => [
                'child_routes' => [
                    'eadimport' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/eadimport',
                            'defaults' => [
                                '__NAMESPACE__' => 'EADImport\Controller\Admin',
                                'controller' => 'Index',
                                'action' => 'load',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'map' => [
                                'type' => 'Literal',
                                'options' => [
                                    'route' => '/map',
                                    'defaults' => [
                                        '__NAMESPACE__' => 'EADImport\Controller\Admin',
                                        'controller' => 'Index',
                                        'action' => 'map',
                                    ],
                                ],
                            ],
                            'import' => [
                                'type' => 'Literal',
                                'options' => [
                                    'route' => '/import',
                                    'defaults' => [
                                        '__NAMESPACE__' => 'EADImport\Controller\Admin',
                                        'controller' => 'Index',
                                        'action' => 'import',
                                    ],
                                ],
                            ],
                            'past-imports' => [
                                'type' => 'Literal',
                                'options' => [
                                    'route' => '/past-imports',
                                    'defaults' => [
                                        '__NAMESPACE__' => 'EADImport\Controller\Admin',
                                        'controller' => 'Index',
                                        'action' => 'past-imports',
                                    ],
                                ],
                            ],
                            'mapping-model' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route' => '/mapping-model[/:action]',
                                    'constraints' => [
                                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                    ],
                                    'defaults' => [
                                        '__NAMESPACE__' => 'EADImport\Controller\Admin',
                                        'controller' => 'MappingModel',
                                        'action' => 'browse',
                                    ],
                                ],
                            ],
                            'mapping-model-id' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route' => '/mapping-model/:id[/:action]',
                                    'constraints' => [
                                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                        'id' => '\d+',
                                    ],
                                    'defaults' => [
                                        '__NAMESPACE__' => 'EADImport\Controller\Admin',
                                        'controller' => 'MappingModel',
                                        'action' => 'show',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => 'gettext',
                'base_dir' => dirname(__DIR__) . '/language',
                'pattern' => '%s.mo',
                'text_domain' => null,
            ],
        ],
    ],
];
