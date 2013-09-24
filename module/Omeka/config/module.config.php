<?php
return array(
    'router' => array(
        'routes' => array(
            'api' => array(
                'type' => 'Zend\Mvc\Router\Http\Segment',
                'options' => array(
                    'route'    => '/api/:resource[/:id]',
                    'defaults' => array(
                        'controller' => 'Omeka\Controller\Api\Index',
                        'action'     => 'index',
                    ),
                ),
            ),
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            'Omeka\Controller\Api\Index' => 'Omeka\Controller\Api\IndexController'
        ),
    ),
    'view_manager' => array(
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),
    'api_manager' => array(
        'resources' => array(
            'sites' => array(
                'adapter_class' => 'Omeka\Api\Adapter\Db',
                'adapter_data' => array(
                    'entity_class' => 'Omeka\Model\Entity\Site',
                ),
                'operations' => array(
                    \Omeka\Api\Request::SEARCH,
                ),
            ),
        ),
    ),
);
