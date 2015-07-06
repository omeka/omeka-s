<?php
return array(
    'navigation' => array(
        'admin' => array(
            array(
                'label'      => 'Items',
                'class'      => 'items',
                'route'      => 'admin/default',
                'controller' => 'item',
                'action'     => 'browse',
                'resource'   => 'Omeka\Controller\Admin\Item',
                'privilege'  => 'browse',
                'pages' => array(
                    array(
                        'route'      => 'admin/id',
                        'controller' => 'item',
                        'visible'    => false,
                    ),
                ),
            ),
            array(
                'label'      => 'Item Sets',
                'class'      => 'item-sets',
                'route'      => 'admin/default',
                'controller' => 'item-set',
                'action'     => 'browse',
                'resource'   => 'Omeka\Controller\Admin\ItemSet',
                'privilege'  => 'browse',
                'pages' => array(
                    array(
                        'route'      => 'admin/id',
                        'controller' => 'item-set',
                        'visible'    => false,
                    ),
                ),
            ),
            array(
                'label'      => 'Vocabularies',
                'class'      => 'vocabularies',
                'route'      => 'admin/default',
                'controller' => 'vocabulary',
                'action'     => 'browse',
                'resource'   => 'Omeka\Controller\Admin\Vocabulary',
                'privilege'  => 'browse',
                'pages' => array(
                    array(
                        'route'      => 'admin/id',
                        'controller' => 'vocabulary',
                        'visible'    => false,
                    ),
                ),
            ),
            array(
                'label'      => 'Resource Templates',
                'class'      => 'resource-templates',
                'route'      => 'admin/default',
                'controller' => 'resource-template',
                'action'     => 'browse',
                'resource'   => 'Omeka\Controller\Admin\ResourceTemplate',
                'privilege'  => 'browse',
                'pages'      => array(
                    array(
                        'route'      => 'admin/id',
                        'controller' => 'resource-template',
                        'visible'    => false,
                    ),
                    array(
                        'route'      => 'admin/default',
                        'controller' => 'resource-template',
                        'visible'    => false,
                    ),
                ),
            ),
            array(
                'label'      => 'Users',
                'class'      => 'users',
                'route'      => 'admin/default',
                'controller' => 'user',
                'action'     => 'browse',
                'resource'   => 'Omeka\Controller\Admin\User',
                'privilege'  => 'browse',
                'pages' => array(
                    array(
                        'route'      => 'admin/id',
                        'controller' => 'user',
                        'visible'    => false,
                    ),
                ),
            ),
            array(
                'label'      => 'Modules',
                'class'      => 'modules',
                'route'      => 'admin/default',
                'controller' => 'module',
                'action'     => 'browse',
                'resource'   => 'Omeka\Controller\Admin\Module',
                'privilege'  => 'browse',
            ),
            array(
                'label'      => 'Jobs',
                'class'      => 'jobs',
                'route'      => 'admin/default',
                'controller' => 'job',
                'action'     => 'browse',
                'resource'   => 'Omeka\Controller\Admin\Job',
                'privilege'  => 'browse',
            ),
            array(
                'label'      => 'Sites',
                'class'      => 'sites',
                'route'      => 'admin/site',
                'resource'   => 'Omeka\Controller\SiteAdmin\Index',
                'privilege'  => 'index',
            ),
            array(
                'label'      => 'Settings',
                'class'      => 'settings',
                'route'      => 'admin/default',
                'controller' => 'setting',
                'action'     => 'browse',
                'resource'   => 'Omeka\Controller\Admin\Setting',
                'privilege'  => 'browse',
            ),
        ),
        'user' => array(
            array(
                'label'         => 'User Information',
                'route'         => 'admin/id',
                'action'        => 'edit',
                'useRouteMatch' => true,
            ),
            array(
                'label'         => 'Password',
                'route'         => 'admin/id',
                'action'        => 'change-password',
                'useRouteMatch' => true,
            ),
            array(
                'label'         => 'API Keys',
                'route'         => 'admin/id',
                'action'        => 'edit-keys',
                'useRouteMatch' => true,
            ),
        ),
    ),
);
