<?php
return [
    'router' => [
        'routes' => [
            'top' => [
                'type' => \Zend\Router\Http\Literal::class,
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
                'type' => \Zend\Router\Http\Segment::class,
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
                        'type' => \Zend\Router\Http\Segment::class,
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
                        'type' => \Zend\Router\Http\Segment::class,
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
                        'type' => \Zend\Router\Http\Segment::class,
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
                    'page' => [
                        'type' => \Zend\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/page/:page-slug',
                            'defaults' => [
                                'controller' => 'Page',
                                'action' => 'show',
                            ],
                        ],
                    ],
                ],
            ],
            'admin' => [
                'type' => \Zend\Router\Http\Literal::class,
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
                        'type' => \Zend\Router\Http\Segment::class,
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
                        'type' => \Zend\Router\Http\Segment::class,
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
                        'type' => \Zend\Router\Http\Literal::class,
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
                                'type' => \Zend\Router\Http\Segment::class,
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
                                        'type' => \Zend\Router\Http\Segment::class,
                                        'options' => [
                                            'route' => '[/:action]',
                                            'constraints' => [
                                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                            ],
                                        ],
                                    ],
                                    'page' => [
                                        'type' => \Zend\Router\Http\Segment::class,
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
                                                'type' => \Zend\Router\Http\Segment::class,
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
                                'type' => \Zend\Router\Http\Literal::class,
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
                'type' => \Zend\Router\Http\Literal::class,
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
                        'type' => \Zend\Router\Http\Segment::class,
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
                'type' => \Zend\Router\Http\Literal::class,
                'options' => [
                    'route' => '/api-context',
                    'defaults' => [
                        'controller' => 'Omeka\Controller\Api',
                        'action' => 'context',
                    ],
                ],
            ],
            'install' => [
                'type' => \Zend\Router\Http\Regex::class,
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
                'type' => \Zend\Router\Http\Regex::class,
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
                'type' => \Zend\Router\Http\Regex::class,
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
                'type' => \Zend\Router\Http\Regex::class,
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
                'type' => \Zend\Router\Http\Regex::class,
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
                'type' => \Zend\Router\Http\Segment::class,
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
                'type' => \Zend\Router\Http\Regex::class,
                'options' => [
                    'regex' => '/forgot-password(/.*)?',
                    'spec' => '/forgot-password',
                    'defaults' => [
                        'controller' => 'Omeka\Controller\Login',
                        'action' => 'forgot-password',
                    ],
                ],
            ],
        ],
    ],
];
