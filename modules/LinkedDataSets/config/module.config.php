<?php

namespace LinkedDataSets;

use LinkedDataSets\Infrastructure\Services\Factories\ApiManagerHelperFactory;
use LinkedDataSets\Infrastructure\Services\Factories\CatalogDumpServiceFactory;
use LinkedDataSets\Infrastructure\Services\Factories\DistributionServiceFactory;
use LinkedDataSets\Infrastructure\Services\Factories\FileCompressionServiceFactory;
use LinkedDataSets\Infrastructure\Services\Factories\ItemSetCrawlerFactory;
use LinkedDataSets\Infrastructure\Services\Factories\UpdateDistributionServiceFactory;
use LinkedDataSets\Infrastructure\Services\Factories\UriHelperFactory;

return [
    'service_manager' => [
        'factories' => [
            'LDS\DistributionService' => DistributionServiceFactory::class,
            'LDS\ItemSetCrawler' => ItemSetCrawlerFactory::class,
            'LDS\FileCompressionService' => FileCompressionServiceFactory::class,
            'LDS\ApiManagerHelper' => ApiManagerHelperFactory::class,
            'LDS\UriHelper' => UriHelperFactory::class,
            'LDS\CatalogDumpService' => CatalogDumpServiceFactory::class,
            'LDS\UpdateDistributionService' => UpdateDistributionServiceFactory::class,
        ]
    ],
    'dependencies' => [
        'modules' => [
            ['name' => 'CustomVocab', 'version' => '1.7.1'],
            ['name' => 'NumericDataTypes', 'version' => '1.11.0'],
            ],
        ],
    'folders' => [
        ["path" => 'files/datacatalogs/'],
        ["path" => 'files/datadumps/'],
    ],
];
