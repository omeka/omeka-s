<?php
namespace Omeka\Media\Ingester;

use Omeka\Api\Request;
use Omeka\Model\Entity\Media;
use Omeka\Stdlib\ErrorStore;

/**
 * Interface for ingesters for Media.
 *
 * Each ingester corresponds to one media type.
 */
interface IngesterInterface
{
    /**
     * Validate a request for compliance with this media type.
     *
     * @param Request $request The API request to validate
     * @param ErrorStore $errorStore
     */
    public function validateRequest(Request $request, ErrorStore $errorStore);

    /**
     * Process the ingest request and update the Media entity.
     *
     * @param Media $media
     * @param Request $request
     * @param ErrorStore $errorStore
     */
    public function ingest(Media $media, Request $request, ErrorStore $errorStore);
}
