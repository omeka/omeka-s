<?php
namespace CustomVocab;

return [
    'api_adapters' => [
        'invokables' => [
            'custom_vocabs' => Api\Adapter\CustomVocabAdapter::class,
        ],
    ],
    'entity_manager' => [
        'mapping_classes_paths' => [
            dirname(__DIR__) . '/src/Entity',
        ],
        'proxy_paths' => [
            dirname(__DIR__) . '/data/doctrine-proxies',
        ],
    ],
    'service_manager' => [
        'factories' => [
            'CustomVocab\ImportExport' => Service\Stdlib\ImportExportFactory::class,
        ],
    ],
    'data_types' => [
        'abstract_factories' => [
            Service\DataType\CustomVocabFactory::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view',
        ],
    ],
    'form_elements' => [
        'factories' => [
            Form\Element\CustomVocabSelect::class => Service\Form\Element\CustomVocabSelectFactory::class,
        ],
    ],
    'controllers' => [
        'factories' => [
            'CustomVocab\Controller\Admin\Index' => Service\Controller\Admin\IndexControllerFactory::class,
        ],
    ],
    'browse_defaults' => [
        'admin' => [
            'custom_vocabs' => [
                'sort_by' => 'label',
                'sort_order' => 'asc',
            ],
        ],
    ],
    'datascribe_data_types' => [
        'factories' => [
            'custom_vocab_select' => Service\DatascribeDataType\CustomVocabSelectFactory::class,
        ],
    ],
    'navigation' => [
        'AdminModule' => [
            [
                'label' => 'Custom Vocab', // @translate
                'route' => 'admin/custom-vocab',
                'resource' => 'CustomVocab\Controller\Admin\Index',
                'privilege' => 'browse',
                'pages' => [
                    [
                        'route' => 'admin/custom-vocab/default',
                        'visible' => false,
                    ],
                    [
                        'route' => 'admin/custom-vocab/id',
                        'visible' => false,
                    ],
                ],
            ],
        ],
    ],
    'router' => [
        'routes' => [
            'admin' => [
                'child_routes' => [
                    'custom-vocab' => [
                        'type' => \Laminas\Router\Http\Literal::class,
                        'options' => [
                            'route' => '/custom-vocab',
                            'defaults' => [
                                '__NAMESPACE__' => 'CustomVocab\Controller\Admin',
                                'controller' => 'index',
                                'action' => 'browse',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'default' => [
                                'type' => \Laminas\Router\Http\Segment::class,
                                'options' => [
                                    'route' => '/:action',
                                    'constraints' => [
                                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                    ],
                                    'defaults' => [
                                        'action' => 'add',
                                    ],
                                ],
                            ],
                            'id' => [
                                'type' => \Laminas\Router\Http\Segment::class,
                                'options' => [
                                    'route' => '/:id[/:action]',
                                    'constraints' => [
                                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                        'id' => '\d+',
                                    ],
                                    'defaults' => [
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
