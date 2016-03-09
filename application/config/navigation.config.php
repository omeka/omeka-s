<?php
return [
    'navigation' => [
        'admin' => [
            [
                'label'      => 'Sites',
                'class'      => 'sites',
                'route'      => 'admin/site',
                'resource'   => 'Omeka\Controller\SiteAdmin\Index',
                'privilege'  => 'index',
            ],
            [
                'label'      => 'Items',
                'class'      => 'items',
                'route'      => 'admin/default',
                'controller' => 'item',
                'action'     => 'browse',
                'resource'   => 'Omeka\Controller\Admin\Item',
                'privilege'  => 'browse',
                'pages' => [
                    [
                        'route'      => 'admin/id',
                        'controller' => 'item',
                        'visible'    => false,
                    ]
                ],
            ],
            [
                'label'      => 'Item Sets',
                'class'      => 'item-sets',
                'route'      => 'admin/default',
                'controller' => 'item-set',
                'action'     => 'browse',
                'resource'   => 'Omeka\Controller\Admin\ItemSet',
                'privilege'  => 'browse',
                'pages' => [
                    [
                        'route'      => 'admin/id',
                        'controller' => 'item-set',
                        'visible'    => false,
                    ]
                ],
            ],
            [
                'label'      => 'Vocabularies',
                'class'      => 'vocabularies',
                'route'      => 'admin/default',
                'controller' => 'vocabulary',
                'action'     => 'browse',
                'resource'   => 'Omeka\Controller\Admin\Vocabulary',
                'privilege'  => 'browse',
                'pages' => [
                    [
                        'route'      => 'admin/id',
                        'controller' => 'vocabulary',
                        'visible'    => false,
                    ],
                ],
            ],
            [
                'label'      => 'Resource Templates',
                'class'      => 'resource-templates',
                'route'      => 'admin/default',
                'controller' => 'resource-template',
                'action'     => 'browse',
                'resource'   => 'Omeka\Controller\Admin\ResourceTemplate',
                'privilege'  => 'browse',
                'pages'      => [
                    [
                        'route'      => 'admin/id',
                        'controller' => 'resource-template',
                        'visible'    => false,
                    ],
                    [
                        'route'      => 'admin/default',
                        'controller' => 'resource-template',
                        'visible'    => false,
                    ],
                ],
            ],
            [
                'label'      => 'Users',
                'class'      => 'users',
                'route'      => 'admin/default',
                'controller' => 'user',
                'action'     => 'browse',
                'resource'   => 'Omeka\Controller\Admin\User',
                'privilege'  => 'browse',
                'pages' => [
                    [
                        'route'      => 'admin/id',
                        'controller' => 'user',
                        'visible'    => false,
                    ],
                ],
            ],
            [
                'label'      => 'Modules',
                'class'      => 'modules',
                'route'      => 'admin/default',
                'controller' => 'module',
                'action'     => 'browse',
                'resource'   => 'Omeka\Controller\Admin\Module',
                'privilege'  => 'browse',
            ],
            [
                'label'      => 'Jobs',
                'class'      => 'jobs',
                'route'      => 'admin/default',
                'controller' => 'job',
                'action'     => 'browse',
                'resource'   => 'Omeka\Controller\Admin\Job',
                'privilege'  => 'browse',
            ],
            [
                'label'      => 'Settings',
                'class'      => 'settings',
                'route'      => 'admin/default',
                'controller' => 'setting',
                'action'     => 'browse',
                'resource'   => 'Omeka\Controller\Admin\Setting',
                'privilege'  => 'browse',
            ],
        ],
        'user' => [
            [
                'label'         => 'User Information',
                'route'         => 'admin/id',
                'action'        => 'edit',
                'useRouteMatch' => true,
            ],
            [
                'label'         => 'Password',
                'route'         => 'admin/id',
                'action'        => 'change-password',
                'useRouteMatch' => true,
            ],
            [
                'label'         => 'API Keys',
                'route'         => 'admin/id',
                'action'        => 'edit-keys',
                'useRouteMatch' => true,
            ],
        ],
        'site' => [
            [
                'label'         => 'Site Info',
                'class'         => 'site-info',
                'route'         => 'admin/site/default',
                'action'        => 'edit',
                'useRouteMatch' => true
            ],
            [
                'label'         => 'Pages',
                'class'         => 'pages',
                'route'         => 'admin/site/page',
                'action'        => 'index',
                'useRouteMatch' => true,
                'pages'         => [
                    [
                        'route'      => 'admin/site/default',
                        'action'     => 'add-page',
                        'visible'    => false,
                    ],
                    [
                        'route'      => 'admin/site/page/default',
                        'visible'    => false,
                    ],
                ],
            ],
            [
                'label'         => 'Navigation',
                'class'         => 'navigation',
                'route'         => 'admin/site/default',
                'action'        => 'navigation',
                'useRouteMatch' => true
            ],
            [
                'label'         => 'Item Pool',
                'class'         => 'item-pool',
                'route'         => 'admin/site/default',
                'action'        => 'item-pool',
                'useRouteMatch' => true
            ],
            [
                'label'         => 'User Permissions',
                'class'         => 'users',
                'route'         => 'admin/site/default',
                'action'        => 'users',
                'useRouteMatch' => true
            ],
            [
                'label'         => 'Theme',
                'class'         => 'theme',
                'route'         => 'admin/site/default',
                'action'        => 'theme',
                'useRouteMatch' => true
            ],
            [
                'label'         => 'Settings',
                'class'         => 'settings',
                'route'         => 'admin/site/default',
                'action'        => 'settings',
                'useRouteMatch' => true
            ],
        ]
    ],
];
