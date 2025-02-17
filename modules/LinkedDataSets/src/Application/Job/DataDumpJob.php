<?php

declare(strict_types=1);

namespace LinkedDataSets\Application\Job;

use EasyRdf\Exception;
use EasyRdf\Graph;
use EasyRdf\RdfNamespace;
use Laminas\Log\Logger;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\ServiceLocatorInterface;
use LinkedDataSets\Application\Dto\DistributionDto;
use LinkedDataSets\Application\Service\DistributionService;
use LinkedDataSets\Application\Service\ItemSetCrawler;
use LinkedDataSets\Application\Service\UpdateDistributionService;
use LinkedDataSets\Infrastructure\Exception\DistributionNotDefinedException;
use LinkedDataSets\Infrastructure\Exception\FormatNotSupportedException;
use LinkedDataSets\Infrastructure\Services\FileCompressionService;
use LinkedDataSets\Application\Job\RecreateDataCatalogsJob;
use Omeka\Api\Manager;
use Omeka\Entity\Job;
use Omeka\Job\AbstractJob;
use Omeka\Job\Exception\InvalidArgumentException;

final class DataDumpJob extends AbstractJob
{
    // in php 8.1 convert to enum
    private const DUMP_FORMATS = [
        "turtle" => "ttl",
        "ntriples" => "nt",
        "jsonld" => "jsonld",
        "rdfxml" => "xml",
    ];

    private const DUMP_PATH = '/files/datadumps/';

    protected ?Logger $logger = null;
    protected $id;
    protected ?Manager $api;
    protected $uriHelper;
    protected ?DistributionService $distributionService;
    protected ?ItemSetCrawler $itemSetCrawler;
    protected ?FileCompressionService $compressionService;
    protected UpdateDistributionService $updateDistributionService;
    protected $dispatcher;

    public function __construct(Job $job, ServiceLocatorInterface $serviceLocator)
    {
        parent::__construct($job, $serviceLocator);
        $this->logger = $serviceLocator->get('Omeka\Logger');
        if (!$this->logger) {
            throw new ServiceNotFoundException('The logger service is not found');
        }
        $this->uriHelper = $serviceLocator->get('LDS\UriHelper');
        if (!$this->uriHelper) {
            throw new ServiceNotFoundException('The UriHelper service is not found');
        }
        $this->id = $this->getArg('id');
        if (!$this->id) {
            throw new InvalidArgumentException('No id was provided to the job');
        }
        $this->api = $serviceLocator->get('Omeka\ApiManager');
        if (!$this->api) {
            throw new ServiceNotFoundException('The API manager is not found');
        }
        $this->distributionService = $serviceLocator->get('LDS\DistributionService');
        if (!$this->distributionService) {
            throw new ServiceNotFoundException('The Disitribution Service is not found');
        }
        $this->itemSetCrawler = $serviceLocator->get('LDS\ItemSetCrawler');
        if (!$this->itemSetCrawler) {
            throw new ServiceNotFoundException('The ItemSetCrawler is not found');
        }
        $this->compressionService = $serviceLocator->get('LDS\FileCompressionService');
        if (!$this->compressionService) {
            throw new ServiceNotFoundException('The compression service is not found');
        }
        $this->api = $serviceLocator->get('Omeka\ApiManager');
        if (!$this->api) {
            throw new ServiceNotFoundException('The API manager is not found');
        }
        $this->updateDistributionService = $serviceLocator->get('LDS\UpdateDistributionService');
    }


    /**
     * @throws Exception
     */
    public function perform(): void
    {
        $apiUrl = $this->uriHelper->constructUri() . "/api/items/{$this->id}";

        # Step 0 - create graph and define prefix schema:
        RdfNamespace::set('schema', 'https://schema.org/');
        $graph = new Graph(); //dep injection?

        $folder = $this->createTemporaryFolder();

        # Step 1 - get lds dataset
        $graph->parse($apiUrl, 'jsonld');

        try {
            $distributions = $this->distributionService->getDistributions($graph);
        } catch (FormatNotSupportedException $e) {
            $this->logger->info(
                "Format {$e->format} for DataSet {$this->id} is not supported, no dump is created"
            );
            return;
        } catch (DistributionNotDefinedException $e) {
            $this->logger->info(
                "DataSet {$this->id} has no distribution defined"
            );
            return;
        }

        $itemSets = $graph->resourcesMatching("^schema:isBasedOn");

        if (!$itemSets) {
            $this->logger->info(
                "DataSet {$this->id} has no item_sets defined"
            );
            return;
        }

        foreach ($itemSets as $itemSet) {
            $itemSetId = $this->getIdFromPath($itemSet->getUri());
            $this->itemSetCrawler->crawl($itemSetId, $folder);
        }

        $mergedFile = $this->mergeTriples($folder);

        $graph = new \EasyRdf\Graph();
        $graph->parseFile($mergedFile);

        /** @var DistributionDto $distribution */
        foreach ($distributions as $distribution) {
            $endFile = OMEKA_PATH . self::DUMP_PATH . $distribution->getFilename();

            $convertFolder = $this->createTemporaryFolder();
            $tempFileName = uniqid();
            $tempPath = $convertFolder . '/' . $tempFileName;

            file_put_contents(
                $tempPath,
                $graph->serialise($this->conversionFormat($distribution->getFormat()))
            );

            // determine if the file needs to be compressed
            if (substr($distribution->getFormat(), -4) === 'gzip') {
                $compressedFile = $this->compressionService->gzCompressFile($tempPath);
                rename($compressedFile, $endFile);
            } else {
                rename($tempPath, $endFile);
            }

            $this->updateDistributionService->update(
                $distribution->getId(),
                $this->uriHelper->constructUri() . self::DUMP_PATH . $distribution->getFilename(),
                (new \DateTime('today'))->format('Y-m-d'),
                (new \SplFileInfo($endFile))->getSize()
            );
        }

        $this->dispatcher->dispatch(RecreateDataCatalogsJob::class, []); // update distributie
    }

    private function createTemporaryFolder(): string
    {
        $dir = sys_get_temp_dir();
        $tmp = uniqid('lds_');
        $path = $dir . '/' . $tmp;
        mkdir($path);
        return $path;
    }

    private function getIdFromPath($uri): int
    {
        $path = parse_url($uri, PHP_URL_PATH);
        $segments = explode('/', $path);
        $id = end($segments);
        return (int) $id;
    }

    protected function mergeTriples(string $folder): string
    {
        $generatedTriples = glob($folder . "/*.nt");

        // Name of the output file
        $mergedFile = "$folder/merged_file.nt";
        // Open the output file for writing
        $handle = fopen($mergedFile, "w");

        // Loop through the file array and append each file to the output file
        foreach ($generatedTriples as $file) {
            // Open the current file for reading
            $handle2 = fopen($file, "r");
            // Read the contents of the current file and append it to the output file
            fwrite($handle, fread($handle2, filesize($file)));
            // Close the current file
            fclose($handle2);
        }
        // Close the output file
        fclose($handle);

        return $mergedFile;
    }

    private function conversionFormat($format): string
    {
        if (str_contains($format, 'text/turtle')) {
            return 'ttl';
        }
        if (str_contains($format, 'application/rdf+xml')) {
            return 'xml';
        }
        if (str_contains($format, 'application/n-triples')) {
            return 'nt';
        }
        if (str_contains($format, 'application/ld+json')) {
            return 'jsonld';
        }
        throw new \Exception('format not found');
    }
}
