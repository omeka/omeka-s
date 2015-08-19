<?php
return array(
    'router' => array(
        'routes' => array(
            'top' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Omeka\Controller',
                        'controller'    => 'Index',
                        'action'        => 'index',
                    ),
                ),
            ),
            'site' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/s/:site-slug',
                    'constraints' => array(
                        'site-slug'  => '[a-zA-Z0-9_-]+',
                    ),
                    'defaults' => array(
                        '__NAMESPACE__' => 'Omeka\Controller\Site',
                        '__SITE__'      => true,
                        'controller'    => 'Index',
                        'action'        => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'browse' => array(
                        'type' => 'Regex',
                        'options' => array(
                            'regex' => '/browse(/.*)?',
                            'spec' => '/browse',
                            'defaults' => array(
                                'controller' => 'Index',
                                'action' => 'browse',
                             ),
                        ),
                    ),
                    'page' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/page/:page-slug[/]',
                            'defaults' => array(
                                'controller' => 'Page',
                                'action' => 'show',
                            ),
                        ),
                    ),
                ),
            ),
            'admin' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/admin',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Omeka\Controller\Admin',
                        '__ADMIN__'     => true,
                        'controller'    => 'Index',
                        'action'        => 'browse',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'default' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/[:controller[/:action]]',
                            'constraints' => array(
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                            'defaults' => array(
                                'action' => 'browse',
                            ),
                        ),
                    ),
                    'id' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/:controller/:id[/[:action]]',
                            'constraints' => array(
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'id'         => '\d+',
                            ),
                            'defaults' => array(
                                'action' => 'show',
                            ),
                        ),
                    ),
                    'site' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route' => '/manage',
                            'defaults' => array(
                                '__NAMESPACE__' => 'Omeka\Controller\SiteAdmin',
                                'controller'    => 'Index',
                                'action'        => 'index',
                            ),
                        ),
                        'may_terminate' => true,
                        'child_routes' => array(
                            'default' => array(
                                'type' => 'Segment',
                                'options' => array(
                                    'route' => '/site/:site-slug[/:action]',
                                    'constraints' => array(
                                        'site-slug'  => '[a-zA-Z0-9_-]+',
                                        'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                                    ),
                                    'defaults' => array(
                                        'action' => 'edit',
                                    ),
                                ),
                            ),
                            'add' => array(
                                'type' => 'Literal',
                                'options' => array(
                                    'route' => '/add-site',
                                    'defaults' => array(
                                        'action' => 'add',
                                    ),
                                ),
                            ),
                            'page' => array(
                                'type' => 'Segment',
                                'options' => array(
                                    'route' => '/page/:site-slug/:page-slug[/:action]',
                                    'constraints' => array(
                                        'site-slug'  => '[a-zA-Z0-9_-]+',
                                        'page-slug'  => '[a-zA-Z0-9_-]+',
                                        'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                                    ),
                                    'defaults' => array(
                                        'controller' => 'Page',
                                        'action' => 'edit',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            'api' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/api',
                    'defaults' => array(
                        'controller' => 'Omeka\Controller\Api',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'default' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '[/:resource[/:id]][/]',
                            'constraints' => array(
                                'resource' => '[a-zA-Z0-9_-]+',
                            ),
                        ),
                    ),
                ),
            ),
            'install' => array(
                'type' => 'Regex',
                'options' => array(
                    'regex' => '/install(/.*)?',
                    'spec' => '/install',
                    'defaults' => array(
                        'controller' => 'Omeka\Controller\Install',
                        'action' => 'index',
                     ),
                ),
            ),
            'migrate' => array(
                'type' => 'Regex',
                'options' => array(
                    'regex' => '/migrate(/.*)?',
                    'spec' => '/migrate',
                    'defaults' => array(
                        'controller' => 'Omeka\Controller\Migrate',
                        'action' => 'index',
                     ),
                ),
            ),
            'maintenance' => array(
                'type' => 'Regex',
                'options' => array(
                    'regex' => '/maintenance(/.*)?',
                    'spec' => '/maintenance',
                    'defaults' => array(
                        'controller' => 'Omeka\Controller\Maintenance',
                        'action' => 'index',
                     ),
                ),
            ),
            'login' => array(
                'type' => 'Regex',
                'options' => array(
                    'regex' => '/login(/.*)?',
                    'spec' => '/login',
                    'defaults' => array(
                        'controller' => 'Omeka\Controller\Login',
                        'action' => 'login',
                     ),
                ),
            ),
            'logout' => array(
                'type' => 'Regex',
                'options' => array(
                    'regex' => '/logout(/.*)?',
                    'spec' => '/logout',
                    'defaults' => array(
                        'controller' => 'Omeka\Controller\Login',
                        'action' => 'logout',
                     ),
                ),
            ),
            'create-password' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/create-password/:key',
                    'constraints' => array(
                        'key' => '[a-zA-Z0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Omeka\Controller\Login',
                        'action' => 'create-password',
                    ),
                ),
            ),
            'forgot-password' => array(
                'type' => 'Regex',
                'options' => array(
                    'regex' => '/forgot-password(/.*)?',
                    'spec' => '/forgot-password',
                    'defaults' => array(
                        'controller' => 'Omeka\Controller\Login',
                        'action' => 'forgot-password',
                     ),
                ),
            ),
        ),
    ),
);
