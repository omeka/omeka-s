<?php
return [
    'navigation' => [
        'AdminModule' => [
        ],
        'AdminSite' => [
            [
                'label' => 'Sites', // @translate
                'class' => 'sites',
                'route' => 'admin/site',
                'resource' => 'Omeka\Controller\SiteAdmin\Index',
                'privilege' => 'index',
                'pages' => [
                    [
                        'route' => 'admin/site',
                        'visible' => false,
                    ],
                    [
                        'route' => 'admin/site/add',
                        'visible' => false,
                    ],
                    [
                        'route' => 'admin/site/slug',
                        'visible' => false,
                    ],
                    [
                        'route' => 'admin/site/slug/page',
                        'visible' => false,
                    ],
                    [
                        'route' => 'admin/site/slug/action',
                        'visible' => false,
                    ],
                    [
                        'route' => 'admin/site/slug/page/default',
                        'visible' => false,
                    ],
                ],
            ],
        ],
        'AdminResource' => [
            [
                'label' => 'Items', // @translate
                'class' => 'items',
                'route' => 'admin/default',
                'controller' => 'item',
                'action' => 'browse',
                'resource' => 'Omeka\Controller\Admin\Item',
                'privilege' => 'browse',
                'pages' => [
                    [
                        'route' => 'admin/id',
                        'controller' => 'item',
                        'visible' => false,
                    ],
                    [
                        'route' => 'admin/default',
                        'controller' => 'item',
                        'visible' => false,
                    ],
                    [
                        'label' => 'Media', // @translate
                        'route' => 'admin/default',
                        'controller' => 'media',
                        'visible' => true,
                        'pages' => [
                            [
                                'route' => 'admin/id',
                                'controller' => 'media',
                                'visible' => false,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'label' => 'Item sets', // @translate
                'class' => 'item-sets',
                'route' => 'admin/default',
                'controller' => 'item-set',
                'action' => 'browse',
                'resource' => 'Omeka\Controller\Admin\ItemSet',
                'privilege' => 'browse',
                'pages' => [
                    [
                        'route' => 'admin/id',
                        'controller' => 'item-set',
                        'visible' => false,
                    ],
                    [
                        'route' => 'admin/default',
                        'controller' => 'item-set',
                        'visible' => false,
                    ],
                ],
            ],
            [
                'label' => 'Vocabularies', // @translate
                'class' => 'vocabularies',
                'route' => 'admin/default',
                'controller' => 'vocabulary',
                'action' => 'browse',
                'resource' => 'Omeka\Controller\Admin\Vocabulary',
                'privilege' => 'browse',
                'pages' => [
                    [
                        'route' => 'admin/id',
                        'controller' => 'vocabulary',
                        'visible' => false,
                    ],
                    [
                        'route' => 'admin/default',
                        'controller' => 'vocabulary',
                        'visible' => false,
                    ],
                ],
            ],
            [
                'label' => 'Resource templates', // @translate
                'class' => 'resource-templates',
                'route' => 'admin/default',
                'controller' => 'resource-template',
                'action' => 'browse',
                'resource' => 'Omeka\Controller\Admin\ResourceTemplate',
                'privilege' => 'browse',
                'pages' => [
                    [
                        'route' => 'admin/id',
                        'controller' => 'resource-template',
                        'visible' => false,
                    ],
                    [
                        'route' => 'admin/default',
                        'controller' => 'resource-template',
                        'visible' => false,
                    ],
                ],
            ],
        ],
        'AdminGlobal' => [
            [
                'label' => 'Users', // @translate
                'class' => 'users',
                'route' => 'admin/default',
                'controller' => 'user',
                'action' => 'browse',
                'resource' => 'Omeka\Controller\Admin\User',
                'privilege' => 'browse',
                'pages' => [
                    [
                        'route' => 'admin/id',
                        'controller' => 'user',
                        'visible' => false,
                    ],
                    [
                        'route' => 'admin/default',
                        'controller' => 'user',
                        'visible' => false,
                    ],
                ],
            ],
            [
                'label' => 'Modules', // @translate
                'class' => 'modules',
                'route' => 'admin/default',
                'controller' => 'module',
                'action' => 'browse',
                'resource' => 'Omeka\Controller\Admin\Module',
                'privilege' => 'browse',
            ],
            [
                'label' => 'Jobs', // @translate
                'class' => 'jobs',
                'route' => 'admin/default',
                'controller' => 'job',
                'action' => 'browse',
                'resource' => 'Omeka\Controller\Admin\Job',
                'privilege' => 'browse',
            ],
            [
                'label' => 'Settings', // @translate
                'class' => 'settings',
                'route' => 'admin/default',
                'controller' => 'setting',
                'action' => 'browse',
                'resource' => 'Omeka\Controller\Admin\Setting',
                'privilege' => 'browse',
            ],
        ],
        'site' => [
            [
                'label' => 'Site info', // @translate
                'class' => 'site-info',
                'route' => 'admin/site/slug',
                'action' => 'edit',
                'privilege' => 'update',
                'useRouteMatch' => true,
            ],
            [
                'label' => 'Pages', // @translate
                'class' => 'pages',
                'route' => 'admin/site/slug/page',
                'action' => 'index',
                'useRouteMatch' => true,
                'pages' => [
                    [
                        'route' => 'admin/site/slug/action',
                        'action' => 'add-page',
                        'visible' => false,
                    ],
                    [
                        'route' => 'admin/site/slug/page/default',
                        'visible' => false,
                    ],
                ],
            ],
            [
                'label' => 'Navigation', // @translate
                'class' => 'navigation',
                'route' => 'admin/site/slug/action',
                'action' => 'navigation',
                'privilege' => 'update',
                'useRouteMatch' => true,
            ],
            [
                'label' => 'Resources', // @translate
                'class' => 'resources',
                'route' => 'admin/site/slug/action',
                'action' => 'resources',
                'privilege' => 'update',
                'useRouteMatch' => true,
            ],
            [
                'label' => 'User permissions', // @translate
                'class' => 'users',
                'route' => 'admin/site/slug/action',
                'action' => 'users',
                'privilege' => 'update',
                'useRouteMatch' => true,
            ],
            [
                'label' => 'Theme', // @translate
                'class' => 'theme',
                'route' => 'admin/site/slug/action',
                'action' => 'theme',
                'privilege' => 'update',
                'useRouteMatch' => true,
            ],
            [
                'label' => 'Settings', // @translate
                'class' => 'settings',
                'route' => 'admin/site/slug/action',
                'action' => 'settings',
                'privilege' => 'update',
                'useRouteMatch' => true,
            ],
        ],
    ],
];
