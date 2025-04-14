<?php

namespace SyncData; // Corrected namespace

use Laminas\ServiceManager\Factory\InvokableFactory;

return [
    'controllers' => [
        'factories' => [
            'SyncData\Controller\Admin\Index' => InvokableFactory::class, // Corrected
        ],
    ],
    'router' => [
        'routes' => [
            'admin' => [
                'child_routes' => [
                    'sync-data' => [ // Corrected route name
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/sync-data',
                            'defaults' => [
                                '__NAMESPACE__' => 'SyncData\Controller\Admin', // Corrected
                                'controller' => 'Index',
                                'action' => 'index',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'sync' => [
                                'type' => 'Literal',
                                'options' => [
                                    'route' => '/sync',
                                    'defaults' => [
                                        'controller' => 'SyncData\Controller\Admin\Index', // Corrected
                                        'action' => 'sync',
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
    'form_elements' => [
        'factories' => [
            Form\ConfigForm::class => InvokableFactory::class,
        ],
    ],
    'service_manager' => [
        'factories' => [
            //  Service\GraphDBService::class => InvokableFactory::class, // If you use a service
        ],
    ],
    'navigation' => [
        'admin' => [
            [
                'label' => 'Sync Data', // Changed label
                'route' => 'admin/sync-data', // Corrected route
                'resource' => 'sync-data', // Corrected resource
                'privilege' => 'browse',
            ],
        ],
    ],
    'acl' => [
        'resources' => [
            'sync-data' => null, // Corrected resource
        ],
        'allow' => [
            'global_admin' => [
                'sync-data' => ['browse', 'sync'], // Corrected resource
            ],
        ],
    ],
];