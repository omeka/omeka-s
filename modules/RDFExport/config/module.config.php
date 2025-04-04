<?php
return [
    'controllers' => [
        'factories' => [
            'RDFExport\Controller\Site\Index' => 'RDFExport\Controller\Site\IndexControllerFactory',
        ],
    ],
    'router' => [
    'routes' => [
        'site' => [
            'child_routes' => [
                'rdf-export' => [
                    'type' => 'Literal',
                    'options' => [
                        'route' => '/rdf-export',
                        'defaults' => [
                            '__NAMESPACE__' => 'RDFExport\Controller\Site',
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
                                    'controller' => 'RDFExport\Controller\Site\Index',
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