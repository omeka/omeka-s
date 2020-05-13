<?php
return [
    'router' => [
        'routes' => [
            'top' => [
                'type' => \Laminas\Router\Http\Literal::class,
                'options' => [
                    'route' => '/',
                    'defaults' => [
                        '__NAMESPACE__' => 'Omeka\Controller',
                        'controller' => 'Index',
                        'action' => 'index',
                    ],
                ],
            ],
            'site' => [
                'type' => \Laminas\Router\Http\Segment::class,
                'options' => [
                    'route' => '/s/:site-slug',
                    'constraints' => [
                        'site-slug' => '[a-zA-Z0-9_-]+',
                    ],
                    'defaults' => [
                        '__NAMESPACE__' => 'Omeka\Controller\Site',
                        '__SITE__' => true,
                        'controller' => 'Index',
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'resource' => [
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/:controller[/:action]',
                            'defaults' => [
                                'action' => 'browse',
                            ],
                            'constraints' => [
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                        ],
                    ],
                    'resource-id' => [
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/:controller/:id[/:action]',
                            'defaults' => [
                                'action' => 'show',
                            ],
                            'constraints' => [
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'id' => '\d+',
                            ],
                        ],
                    ],
                    'item-set' => [
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/item-set/:item-set-id',
                            'defaults' => [
                                'controller' => 'Item',
                                'action' => 'browse',
                            ],
                            'constraints' => [
                                'item-set-id' => '\d+',
                            ],
                        ],
                    ],
                    'page-browse' => [
                        'type' => \Laminas\Router\Http\Literal::class,
                        'options' => [
                            'route' => '/page',
                            'defaults' => [
                                'controller' => 'Page',
                                'action' => 'browse',
                            ],
                        ],
                    ],
                    'page' => [
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/page/:page-slug',
                            'defaults' => [
                                'controller' => 'Page',
                                'action' => 'show',
                            ],
                        ],
                    ],
                    'cross-site-search' => [
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/cross-site-search[/:action]',
                            'defaults' => [
                                'controller' => 'CrossSiteSearch',
                                'action' => 'index',
                            ],
                            'constraints' => [
                                'action' => '[a-zA-Z0-9_-]+',
                            ],
                        ],
                    ],
                ],
            ],
            'admin' => [
                'type' => \Laminas\Router\Http\Literal::class,
                'options' => [
                    'route' => '/admin',
                    'defaults' => [
                        '__NAMESPACE__' => 'Omeka\Controller\Admin',
                        '__ADMIN__' => true,
                        'controller' => 'Index',
                        'action' => 'browse',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'default' => [
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/:controller[/:action]',
                            'constraints' => [
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                'action' => 'browse',
                            ],
                        ],
                    ],
                    'id' => [
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/:controller/:id[/:action]',
                            'constraints' => [
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'id' => '\d+',
                            ],
                            'defaults' => [
                                'action' => 'show',
                            ],
                        ],
                    ],
                    'site' => [
                        'type' => \Laminas\Router\Http\Literal::class,
                        'options' => [
                            'route' => '/site',
                            'defaults' => [
                                '__NAMESPACE__' => 'Omeka\Controller\SiteAdmin',
                                '__SITEADMIN__' => true,
                                'controller' => 'Index',
                                'action' => 'index',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'slug' => [
                                'type' => \Laminas\Router\Http\Segment::class,
                                'options' => [
                                    'route' => '/s/:site-slug',
                                    'constraints' => [
                                        'site-slug' => '[a-zA-Z0-9_-]+',
                                    ],
                                    'defaults' => [
                                        'action' => 'edit',
                                    ],
                                ],
                                'may_terminate' => true,
                                'child_routes' => [
                                    'action' => [
                                        'type' => \Laminas\Router\Http\Segment::class,
                                        'options' => [
                                            'route' => '[/:action]',
                                            'constraints' => [
                                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                            ],
                                        ],
                                    ],
                                    'page' => [
                                        'type' => \Laminas\Router\Http\Segment::class,
                                        'options' => [
                                            'route' => '/page',
                                            'defaults' => [
                                                'controller' => 'Page',
                                                'action' => 'index',
                                            ],
                                        ],
                                        'may_terminate' => true,
                                        'child_routes' => [
                                            'default' => [
                                                'type' => \Laminas\Router\Http\Segment::class,
                                                'options' => [
                                                    'route' => '/:page-slug[/:action]',
                                                    'constraints' => [
                                                        'page-slug' => '[a-zA-Z0-9_-]+',
                                                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                                    ],
                                                    'defaults' => [
                                                        'action' => 'edit',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'add' => [
                                'type' => \Laminas\Router\Http\Literal::class,
                                'options' => [
                                    'route' => '/add',
                                    'defaults' => [
                                        'action' => 'add',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'api' => [
                'type' => \Laminas\Router\Http\Literal::class,
                'options' => [
                    'route' => '/api',
                    'defaults' => [
                        '__API__' => true,
                        'controller' => 'Omeka\Controller\Api',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'default' => [
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '[/:resource[/:id]]',
                            'constraints' => [
                                'resource' => '[a-zA-Z0-9_-]+',
                            ],
                        ],
                    ],
                ],
            ],
            'api-context' => [
                'type' => \Laminas\Router\Http\Literal::class,
                'options' => [
                    'route' => '/api-context',
                    'defaults' => [
                        'controller' => 'Omeka\Controller\Api',
                        'action' => 'context',
                    ],
                ],
            ],
            'install' => [
                'type' => \Laminas\Router\Http\Regex::class,
                'options' => [
                    'regex' => '/install(/.*)?',
                    'spec' => '/install',
                    'defaults' => [
                        'controller' => 'Omeka\Controller\Install',
                        'action' => 'index',
                    ],
                ],
            ],
            'migrate' => [
                'type' => \Laminas\Router\Http\Regex::class,
                'options' => [
                    'regex' => '/migrate(/.*)?',
                    'spec' => '/migrate',
                    'defaults' => [
                        'controller' => 'Omeka\Controller\Migrate',
                        'action' => 'index',
                    ],
                ],
            ],
            'maintenance' => [
                'type' => \Laminas\Router\Http\Regex::class,
                'options' => [
                    'regex' => '/maintenance(/.*)?',
                    'spec' => '/maintenance',
                    'defaults' => [
                        'controller' => 'Omeka\Controller\Maintenance',
                        'action' => 'index',
                    ],
                ],
            ],
            'login' => [
                'type' => \Laminas\Router\Http\Regex::class,
                'options' => [
                    'regex' => '/login(/.*)?',
                    'spec' => '/login',
                    'defaults' => [
                        'controller' => 'Omeka\Controller\Login',
                        'action' => 'login',
                    ],
                ],
            ],
            'logout' => [
                'type' => \Laminas\Router\Http\Regex::class,
                'options' => [
                    'regex' => '/logout(/.*)?',
                    'spec' => '/logout',
                    'defaults' => [
                        'controller' => 'Omeka\Controller\Login',
                        'action' => 'logout',
                    ],
                ],
            ],
            'create-password' => [
                'type' => \Laminas\Router\Http\Segment::class,
                'options' => [
                    'route' => '/create-password/:key',
                    'constraints' => [
                        'key' => '[a-zA-Z0-9]+',
                    ],
                    'defaults' => [
                        'controller' => 'Omeka\Controller\Login',
                        'action' => 'create-password',
                    ],
                ],
            ],
            'forgot-password' => [
                'type' => \Laminas\Router\Http\Regex::class,
                'options' => [
                    'regex' => '/forgot-password(/.*)?',
                    'spec' => '/forgot-password',
                    'defaults' => [
                        'controller' => 'Omeka\Controller\Login',
                        'action' => 'forgot-password',
                    ],
                ],
            ],
            'search' => [
                'type' => \Laminas\Router\Http\Segment::class,
                'options' => [
                    'route' => '/search[/:action]',
                    'defaults' => [
                        'controller' => 'Omeka\Controller\Search',
                        'action' => 'index',
                    ],
                    'constraints' => [
                        'action' => '[a-zA-Z0-9_-]+',
                    ],
                ],
            ],
        ],
    ],
];
