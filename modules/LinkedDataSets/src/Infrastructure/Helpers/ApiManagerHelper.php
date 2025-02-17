<?php

declare(strict_types=1);

namespace LinkedDataSets\Infrastructure\Helpers;

use LinkedDataSets\Infrastructure\Exception\CatalogResourceTemplateDoesNotExists;
use Omeka\Api\Manager;

final class ApiManagerHelper
{
    private const DATACATALOG_LABEL = 'LDS Datacatalog';

    protected $api;

    public function __construct(Manager $api)
    {
        $this->api = $api;
    }

    private function getDatacatalogTemplateId()
    {
        $templates = $this->api->search('resource_templates')
            ->getContent()
        ;

        foreach ($templates as $template) {
            if ($template->label() === self::DATACATALOG_LABEL) {
                $templateId = $template->id();
                break;
            }
        }

        if (!$templateId) {
            throw new CatalogResourceTemplateDoesNotExists(
                'There\'s no resource template for Datacatalogs'
            );
        }

        return $templateId;
    }

    public function getDatacatalogs()
    {
        $catalogResourceTemplateId = $this->getDatacatalogTemplateId();

        $data = ['resource_template_id' => [$catalogResourceTemplateId]];

        return $this->api
            ->search('items', $data)
            ->getContent()
        ;
    }
}
