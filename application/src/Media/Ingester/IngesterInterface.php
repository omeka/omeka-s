<?php
namespace Omeka\Media\Ingester;

use Omeka\Api\Request;
use Omeka\Entity\Media;
use Omeka\Stdlib\ErrorStore;
use Zend\View\Renderer\PhpRenderer;

/**
 * Interface for media ingesters.
 */
interface IngesterInterface
{
    /**
     * Get a human-readable label for this ingester.
     *
     * @return string
     */
    public function getLabel();

    /**
     * Get the name of the renderer for media ingested by this ingester
     *
     * @return string
     */
    public function getRenderer();

    /**
     * Process an ingest (create) request.
     *
     * @param Media $media
     * @param Request $request
     * @param ErrorStore $errorStore
     */
    public function ingest(Media $media, Request $request,
        ErrorStore $errorStore);

    /**
     * Render a form for adding media.
     *
     * @param PhpRenderer $view
     * @param array $options
     * @return string
     */
    public function form(PhpRenderer $view, array $options = []);
}
