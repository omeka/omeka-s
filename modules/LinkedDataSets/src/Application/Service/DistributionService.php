<?php

declare(strict_types=1);

namespace LinkedDataSets\Application\Service;

use EasyRdf\Graph;
use EasyRdf\Resource;
use LinkedDataSets\Application\Dto\DistributionDto;
use LinkedDataSets\Infrastructure\Exception\DistributionNotDefinedException;
use LinkedDataSets\Infrastructure\Exception\FormatNotSupportedException;

final class DistributionService
{
    public function getDistributions(Graph $graph): array
    {
        $distributionItemsArray = [];
        $distributionItems = $graph->resourcesMatching('^schema:distribution');

        if (!$distributionItems) {
            throw new DistributionNotDefinedException('There is no distribution defined');
        }

        foreach ($distributionItems as $distributionItem) {
            $newGraph = $graph::newAndLoad($distributionItem->getUri(), 'jsonld');

            $format = $newGraph
                ->getLiteral($distributionItem->getUri(), 'schema:encodingFormat')
                ->getValue()
            ;

            if ($this->isFormatSupported($format)) {
                $fileName = $newGraph
                    ->getLiteral($distributionItem->getUri(), 'schema:name')
                    ->getValue()
                ;
                $id = $this->getIdFromPath($distributionItem->getUri());

                $distributionItemsArray[] = new DistributionDto($format, $fileName, $id);
            }
        }

        return $distributionItemsArray;
    }

    private function getIdFromPath($uri): int
    {
        $path = parse_url($uri, PHP_URL_PATH);
        $segments = explode('/', $path);
        $id = end($segments);
        return (int) $id;
    }

    private function isFormatSupported($format): bool
    {
        $supportedFormats = [ // TODO: move to config later
            "application/ld+json",
            "application/ld+json+gzip",
            "application/n-triples",
            "application/n-triples+gzip",
            "application/rdf+xml",
            "application/rdf+xml+gzip",
            "text/turtle",
            "text/turtle+gzip",
        ];

        return (in_array($format, $supportedFormats));
    }
}
