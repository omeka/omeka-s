<?php
return array(
    'router' => array(
        'routes' => array(
            'plugin' => array(
                'type' => 'Zend\Mvc\Router\Http\Segment',
                'options' => array(
                    'route'    => '/plugin',
                    'defaults' => array(
                        'controller' => 'Omeka\Controller\Api\Index',
                        'action'     => 'index',
                    ),
                ),
            ),
        ),
    ),
    'api_manager' => array(
        'resources' => array(
            'plugins' => array(),
        ),
    ),
);
