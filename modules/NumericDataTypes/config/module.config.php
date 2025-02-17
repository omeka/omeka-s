<?php
return [
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
    'view_manager' => [
        'template_path_stack' => [
            OMEKA_PATH . '/modules/NumericDataTypes/view',
        ],
    ],
    'entity_manager' => [
        'mapping_classes_paths' => [
            OMEKA_PATH . '/modules/NumericDataTypes/src/Entity',
        ],
        'proxy_paths' => [
            OMEKA_PATH . '/modules/NumericDataTypes/data/doctrine-proxies',
        ],
    ],
    'data_types' => [
        'invokables' => [
            'numeric:timestamp' => NumericDataTypes\DataType\Timestamp::class,
            'numeric:integer' => NumericDataTypes\DataType\Integer::class,
            'numeric:duration' => NumericDataTypes\DataType\Duration::class,
            'numeric:interval' => NumericDataTypes\DataType\Interval::class,
        ],
        'value_annotating' => [
            'numeric:timestamp',
            'numeric:integer',
            'numeric:duration',
            'numeric:interval',
        ],
    ],
    'view_helpers' => [
        'invokables' => [
            'formNumericTimestamp' => NumericDataTypes\View\Helper\Timestamp::class,
            'formNumericInterval' => NumericDataTypes\View\Helper\Interval::class,
            'formNumericDuration' => NumericDataTypes\View\Helper\Duration::class,
            'formNumericInteger' => NumericDataTypes\View\Helper\Integer::class,
            'formNumericConvertToNumeric' => NumericDataTypes\View\Helper\ConvertToNumeric::class,
        ],
        'factories' => [
            'numericPropertySelect' => NumericDataTypes\Service\ViewHelper\NumericPropertySelectFactory::class,
        ],
        'delegators' => [
            'Laminas\Form\View\Helper\FormElement' => [
                NumericDataTypes\Service\Delegator\FormElementDelegatorFactory::class,
            ],
        ],
    ],
    'form_elements' => [
        'factories' => [
            'NumericDataTypes\Form\Element\NumericPropertySelect' => NumericDataTypes\Service\Form\Element\NumericPropertySelectFactory::class,
            'NumericDataTypes\Form\Element\ConvertToNumeric' => NumericDataTypes\Service\Form\Element\ConvertToNumericFactory::class,
        ],
    ],
    'csv_import' => [
        'data_types' => [
            'numeric:timestamp' => [
                'label' => 'Date/Time (ISO 8601)', // @translate
                'adapter' => 'literal',
            ],
            'numeric:interval' => [
                'label' => 'Interval (ISO 8601)', // @translate
                'adapter' => 'literal',
            ],
            'numeric:duration' => [
                'label' => 'Duration (ISO 8601)', // @translate
                'adapter' => 'literal',
            ],
            'numeric:integer' => [
                'label' => 'Integer', // @translate
                'adapter' => 'literal',
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            'NumericDataTypes\Controller\SiteAdmin\FacetedBrowse\Index' => NumericDataTypes\Service\Controller\SiteAdmin\FacetedBrowse\IndexControllerFactory::class,
        ],
    ],
    'faceted_browse_facet_types' => [
        'factories' => [
            'date_after' => NumericDataTypes\Service\FacetedBrowse\FacetType\DateAfterFactory::class,
            'date_before' => NumericDataTypes\Service\FacetedBrowse\FacetType\DateBeforeFactory::class,
            'value_greater_than' => NumericDataTypes\Service\FacetedBrowse\FacetType\ValueGreaterThanFactory::class,
            'value_less_than' => NumericDataTypes\Service\FacetedBrowse\FacetType\ValueLessThanFactory::class,
            'duration_greater_than' => NumericDataTypes\Service\FacetedBrowse\FacetType\DurationGreaterThanFactory::class,
            'duration_less_than' => NumericDataTypes\Service\FacetedBrowse\FacetType\DurationLessThanFactory::class,
            'date_in_interval' => NumericDataTypes\Service\FacetedBrowse\FacetType\DateInIntervalFactory::class,
        ],
    ],
    'datavis_dataset_types' => [
        'invokables' => [
            'count_items_time_series' => NumericDataTypes\Datavis\DatasetType\CountItemsTimeSeries::class,
            'count_items_property_values_time_series' => NumericDataTypes\Datavis\DatasetType\CountItemsPropertyValuesTimeSeries::class,
        ],
    ],
    'datavis_diagram_types' => [
        'invokables' => [
            'line_chart_time_series' => NumericDataTypes\Datavis\DiagramType\LineChartTimeSeries::class,
            'histogram_time_series' => NumericDataTypes\Datavis\DiagramType\HistogramTimeSeries::class,
            'line_chart_time_series_grouped' => NumericDataTypes\Datavis\DiagramType\LineChartTimeSeriesGrouped::class,
        ],
    ],
    'router' => [
        'routes' => [
            'admin' => [
                'child_routes' => [
                    'site' => [
                        'child_routes' => [
                            'slug' => [
                                'child_routes' => [
                                    'numeric-data-types-faceted-browse' => [
                                        'type' => \Laminas\Router\Http\Segment::class,
                                        'options' => [
                                            'route' => '/numeric-data-types-faceted-browse/:controller/:action',
                                            'constraints' => [
                                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                            ],
                                            'defaults' => [
                                                '__NAMESPACE__' => 'NumericDataTypes\Controller\SiteAdmin\FacetedBrowse',
                                                'controller' => 'index',
                                                'action' => 'index',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
