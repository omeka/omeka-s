<?php declare(strict_types=1);

namespace DataTypeRdf;

return [
    'data_types' => [
        'invokables' => [
            'xml' => DataType\Xml::class,
            'json' => DataType\Json::class,
            'boolean' => DataType\Boolean::class,
        ],
        'factories' => [
            'html' => Service\DataType\HtmlFactory::class,
        ],
        'value_annotating' => [
            'html',
            'xml',
            'json',
            'boolean',
        ],
    ],
    'view_helpers' => [
        'invokables' => [
            'ckEditor' => View\Helper\CkEditor::class,
        ],
    ],
    'form_elements' => [
        'invokables' => [
            Form\SettingsFieldset::class => Form\SettingsFieldset::class,
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
    'csv_import' => [
        'data_types' => [
            'html' => [
                'label' => 'Html', // @translate
                'adapter' => 'literal',
            ],
            'xml' => [
                'label' => 'Xml', // @translate
                'adapter' => 'literal',
            ],
            'json' => [
                'label' => 'Json', // @translate
                'adapter' => 'literal',
            ],
            'boolean' => [
                'label' => 'Boolean', // @translate
                'adapter' => 'literal',
            ],
        ],
    ],
    'datatyperdf' => [
        'settings' => [
            'datatyperdf_html_mode_resource' => 'inline',
            'datatyperdf_html_config_resource' => 'default',
        ],
    ],
];
