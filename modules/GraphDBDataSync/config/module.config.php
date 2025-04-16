<?php

use Laminas\Http\Client;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\Form\Factory;

return [
    'controllers' => [
        'factories' => [
            'GraphDBDataSync\Controller\Admin\Index' => 'GraphDBDataSync\Controller\Admin\IndexControllerFactory',
        ],
    ],
    'router' => [
    'routes' => [
        'admin' => [
            'child_routes' => [
                'graphdb_data_sync' => [
                    'type' => 'Literal',
                    'options' => [
                        'route' => '/graphdb-data-sync',
                        'defaults' => [
                            '__NAMESPACE__' => 'GraphDBDataSync\Controller\Admin',
                            'controller' => 'Index',
                            'action' => 'index',
                        ],
                    ],
                    'may_terminate' => true, // Add this line
                    'child_routes' => [
                        'config' => [
                            'type' => 'Literal',
                            'options' => [
                                'route' => '/config',
                                'defaults' => [
                                    'action' => 'config',
                                ],
                            ],
                        ],
                        'extract' => [
                            'type' => 'Literal',
                            'options' => [
                                'route' => '/extract',
                                'defaults' => [
                                    'action' => 'extractData',
                                ],
                            ],
                        ],
                        'sync' => [
                            'type' => 'Literal',
                            'options' => [
                                'route' => '/sync',
                                'defaults' => [
                                    'action' => 'syncToGraphDb', // Nome da nova ação no controller
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
    'service_manager' => [
        'factories' => [
            \Laminas\Http\Client::class => \Laminas\ServiceManager\Factory\InvokableFactory::class,
        ],
    ],
    'form_elements' => [
            'factories' => [
                'GraphDBDataSync\Form\GraphDBConfigForm' => 'Laminas\ServiceManager\Factory\InvokableFactory',
            ],
        ],
];