<?php

declare(strict_types=1);

namespace LinkedDataSets\Application\Service;

use EasyRdf\Graph;
use EasyRdf\RdfNamespace;
use EasyRdf\Resource;
use Laminas\Log\Logger;
use Laminas\Log\LoggerInterface;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;

final class CatalogDumpService
{
    private const DUMP_FORMATS = [
        "turtle" => "ttl",
        "ntriples" => "nt",
        "jsonld" => "jsonld",
        "rdfxml" => "xml",
    ];

    protected ?Logger $logger = null;
    protected $uriHelper;
    protected $id;

    public function __construct(
        LoggerInterface $logger,
        $uriHelper
    ) {
        $this->logger = $logger;
        $this->uriHelper = $uriHelper;
        if (!$this->uriHelper) {
            throw new ServiceNotFoundException('The UriHelper service is not found');
        }
    }

    public function dumpCatalog(int $id): void
    {
        // this logic is based on https://github.com/coret/datasets-in-omeka-s/blob/main/src/datadump.php
        $this->id = $id;
        $apiUrl = $this->uriHelper->constructUri() . "/api/items/{$this->id}";

        # Step 0 - create graph and define prefix schema:
        RdfNamespace::set('schema', 'https://schema.org/');
        $graph = new Graph(); //dep injection?

        # Step 1 - get data catalog
            $graph->parse($apiUrl, 'jsonld');

        foreach ($graph->resources() as $resource) {
            # Step 2 - get all datasets which are part of the data catalog
            $datasets = $resource->allResources("schema:dataset");
            foreach ($datasets as $dataset) {
                $datasetUri = $dataset->getUri();
                $graph->parse($datasetUri, 'jsonld');
            }

            # Step 3 - get all distribution which are part of datasets
            $distributions = $resource->allResources("schema:distribution");

            foreach ($distributions as $distribution) {
                $distributionUri = $distribution->getUri();
                $graph->parse($distributionUri, 'jsonld');
            }

            # Step 4 - get all publishers and creators (of data catalog and datasets)
            $publishers = $resource->allResources("schema:publisher");

            foreach ($publishers as $publisher) {
                $publisherUri = $publisher->getUri();
                $graph->parse($publisherUri, 'jsonld');
            }

            $creators = $resource->allResources("schema:creator");

            foreach ($creators as $creator) {
                $creatorUri = $creator->getUri();
                $graph->parse($creatorUri, 'jsonld');
            }

            $funders = $resource->allResources("schema:funder");

            foreach ($funders as $funder) {
                $funderUri = $funder->getUri();
                $graph->parse($funderUri, 'jsonld');
            }
        }

        # Step 5 - remove Omeka classes and properties (o:)
        $this->removeOmekaTags($graph);

        # Step 6 - output the graph in several serializations
        $this->dumpSerialisedFiles($graph);
    }

    protected function removeOmekaTags(Graph $graph): void
    {
        foreach ($graph->resources() as $resource) {
            if (preg_match("/\/resources\//",$resource->getUri())) {
                foreach($resource->properties() as $pu) {
                    $graph->delete($resource,$pu);
                }
                foreach($resource->propertyUris() as $pu) {
                    $resource->delete($pu);
                }
            } else {
                foreach ($resource->properties() as $property) {
                    if ($property == "rdf:type") {
                        /** @var Resource $item */
                        foreach ($resource->all("rdf:type") as $item) {
                            if (preg_match("/omeka\.org\/s\/vocabs\/o/", $item->getUri())) {
                                $resource->delete("rdf:type", $item);
                            }
                        }
                    }
                }
                foreach ($resource->propertyUris() as $propertyUris) {
                    if (preg_match("/omeka\.org\/s\/vocabs\/o/", $propertyUris)) {
                        $resource->delete($propertyUris);
                    }
                }
            }
        }
    }

    protected function dumpSerialisedFiles(Graph $graph): void // candidate for separate class
    {
        $fileName = "datacatalog-{$this->id}"; // in separate class make a const FILENAME_PREFIX or so
        foreach (self::DUMP_FORMATS as $format => $extension) {
            $content = $graph->serialise($format);
            file_put_contents(OMEKA_PATH . "/files/datacatalogs/{$fileName}." . $extension, $content);
            $this->logger->notice(
                "The file {$fileName}.{$extension} is available at " .
                $this->uriHelper->constructUri() .
                "/files/datacatalogs/{$fileName}." . "$extension"
            );
        }
    }
}
