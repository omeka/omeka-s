<?php
return [
    'controllers' => [
        'factories' => [
            'QueryGraphDB\Controller\Site\Index' => 'QueryGraphDB\Controller\Site\IndexControllerFactory',
        ],
    ],
    'router' => [
        'routes' => [
            'site' => [
                'child_routes' => [
                    'graphdb-query' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/graphdb-query',
                            'defaults' => [
                                '__NAMESPACE__' => 'QueryGraphDB\Controller\Site',
                                'controller' => 'Index',
                                'action' => 'index',
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
];