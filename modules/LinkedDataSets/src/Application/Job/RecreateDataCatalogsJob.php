<?php

declare(strict_types=1);

namespace LinkedDataSets\Application\Job;

use Laminas\Log\Logger;
use Laminas\ServiceManager\ServiceLocatorInterface;
use LinkedDataSets\Application\Service\CatalogDumpService;
use LinkedDataSets\Infrastructure\Helpers\ApiManagerHelper;
use Omeka\Api\Representation\ItemRepresentation;
use Omeka\Entity\Job;
use Omeka\Job\AbstractJob;

final class RecreateDataCatalogsJob extends AbstractJob
{
    protected ?Logger $logger = null;
    protected ApiManagerHelper $apiHelper;
    protected CatalogDumpService $catalogDumpService;

    public function __construct(Job $job, ServiceLocatorInterface $serviceLocator)
    {
        parent::__construct($job, $serviceLocator);

        $this->apiHelper = $serviceLocator->get('LDS\ApiManagerHelper');
        $this->catalogDumpService = $this->serviceLocator->get('LDS\CatalogDumpService');
    }


    public function perform(): void
    {

        $catalogs = $this->apiHelper->getDatacatalogs();

        /** @var ItemRepresentation $catalog */
        foreach ($catalogs as $catalog) {
            $this->catalogDumpService->dumpCatalog($catalog->id());
        }
    }
}
