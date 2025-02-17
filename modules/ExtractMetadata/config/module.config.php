<?php
namespace ExtractMetadata;

return [
    'entity_manager' => [
        'mapping_classes_paths' => [
            sprintf('%s/../src/Entity', __DIR__),
        ],
        'proxy_paths' => [
            sprintf('%s/../data/doctrine-proxies', __DIR__),
        ],
    ],
    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => 'gettext',
                'base_dir' => sprintf('%s/../language', __DIR__),
                'pattern' => '%s.mo',
                'text_domain' => null,
            ],
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            sprintf('%s/../view', __DIR__),
        ],
    ],
    'service_manager' => [
        'factories' => [
            'ExtractMetadata\ExtractorManager' => Service\Extractor\ManagerFactory::class,
            'ExtractMetadata\MapperManager' => Service\Mapper\ManagerFactory::class,
        ],
    ],
    'view_helpers' => [
        'factories' => [
            'extractMetadata' => Service\ViewHelper\ExtractMetadataFactory::class,
        ],
    ],
    'extract_metadata_extractors' => [
        'factories' => [
            'exiftool' => Service\Extractor\ExiftoolFactory::class,
            'tika' => Service\Extractor\TikaFactory::class,
        ],
        'invokables' => [
            'exif' => Extractor\Exif::class,
            'getid3' => Extractor\Getid3::class,
        ],
    ],
    'extract_metadata_mappers' => [
        'factories' => [
            'jsonPointer' => Service\Mapper\JsonPointerFactory::class,
        ],
    ],
    'extract_metadata_extractor_config' => [
        'tika' => [
            'jar_path' => '',
        ],
    ],
];
