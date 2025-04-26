<?php
return [
    'controllers' => [
        'factories' => [
            'AddTriplestore\Controller\Site\Index' => 'AddTriplestore\Controller\Site\IndexControllerFactory',
        ],
    ],
    'router' => [
    'routes' => [
        'site' => [
            'child_routes' => [
                'add-triplestore' => [
                    'type' => 'Literal',
                    'options' => [
                        'route' => '/add-triplestore',
                        'defaults' => [
                            '__NAMESPACE__' => 'AddTriplestore\Controller\Site',
                            'controller' => 'Index',
                            'action' => 'index',
                        ],
                    ],
                    'may_terminate' => true,
                    'child_routes' => [
                        'upload' => [
                            'type' => 'Segment',
                            'options' => [
                                'route' => '/upload',
                                'defaults' => [
                                    'controller' => 'AddTriplestore\Controller\Site\Index',
                                    'action' => 'upload',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
],

    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
    'asset_manager' => [
        'resolver_configs' => [
            'paths' => [
                'AddTriplestore' => __DIR__ . '/../asset', // <--- This is the crucial line
            ],
        ],
    ],
    
];