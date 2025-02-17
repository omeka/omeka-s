<?php
namespace Mapping;

use Osii\Service\ResourceMapper\ResourceMapperFactory;

return [
    'api_adapters' => [
        'invokables' => [
            'mappings' => Api\Adapter\MappingAdapter::class,
            'mapping_features' => Api\Adapter\MappingFeatureAdapter::class,
        ],
    ],
    'entity_manager' => [
        'mapping_classes_paths' => [
            dirname(__DIR__) . '/src/Entity',
        ],
        'proxy_paths' => [
            dirname(__DIR__) . '/data/doctrine-proxies',
        ],
        'data_types' => [
            'geography' => \LongitudeOne\Spatial\DBAL\Types\GeographyType::class,
        ],
        'functions' => [
            'numeric' => [
                'ST_Buffer' => 'Mapping\Spatial\ORM\Query\AST\Functions\StBuffer',
                'ST_Intersects' => 'LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StIntersects',
                'ST_GeomFromText' => 'LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StGeomFromText',
                'ST_GeometryType' => 'LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StGeometryType',
            ],
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view',
        ],
    ],
    'view_helpers' => [
        'invokables' => [
            'formPromptMap' => Collecting\FormPromptMap::class,
            'formMappingCopyCoordinates' => View\Helper\CopyCoordinates::class,
            'formMappingUpdateFeatures' => View\Helper\UpdateFeatures::class,
        ],
        'delegators' => [
            'Laminas\Form\View\Helper\FormElement' => [
                Service\Delegator\FormElementDelegatorFactory::class,
            ],
        ],
    ],
    'form_elements' => [
        'factories' => [
           'Mapping\Form\Element\CopyCoordinates' => Service\Form\Element\CopyCoordinatesFactory::class,
           'Mapping\Form\Element\UpdateFeatures' => Service\Form\Element\UpdateFeaturesFactory::class,
        ],
    ],
    'csv_import' => [
        'mappings' => [
            'items' => [ CsvMapping\CsvMapping::class ],
        ],
    ],
    'omeka2_importer_classes' => [
        Omeka2Importer\GeolocationImporter::class,
    ],
    'osii_resource_mappers' => [
        'factories' => [
            Osii\ResourceMapper\ItemMapping::class => ResourceMapperFactory::class,
        ],
    ],
    'block_layouts' => [
        'factories' => [
            'mappingMap' => Service\BlockLayout\MapFactory::class,
            'mappingMapQuery' => Service\BlockLayout\MapFactory::class,
        ],
    ],
    'navigation_links' => [
        'invokables' => [
            'mapping' => Site\Navigation\Link\MapBrowse::class,
        ],
    ],
    'controllers' => [
        'invokables' => [
            'Mapping\Controller\Site\Index' => Controller\Site\IndexController::class,
        ],
    ],
    'collecting_media_types' => [
        'invokables' => [
            'map' => Collecting\Map::class,
        ],
    ],
    'router' => [
        'routes' => [
            'site' => [
                'child_routes' => [
                    'mapping-map-browse' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/map-browse',
                            'defaults' => [
                                '__NAMESPACE__' => 'Mapping\Controller\Site',
                                'controller' => 'index',
                                'action' => 'browse',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => 'gettext',
                'base_dir' => dirname(__DIR__) . '/language',
                'pattern' => '%s.mo',
                'text_domain' => null,
            ],
        ],
    ],
    'resource_page_block_layouts' => [
        'invokables' => [
            'mapping' => Site\ResourcePageBlockLayout\Mapping::class,
        ],
    ],
    'resource_page_blocks_default' => [
        'items' => [
            'main' => ['mapping'],
        ],
    ],
];
