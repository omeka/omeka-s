<?php
namespace Omeka\Media\Handler;

use Omeka\Api\Representation\MediaRepresentation;
use Omeka\Api\Request;
use Omeka\Entity\Media;
use Omeka\Stdlib\ErrorStore;
use Zend\View\Renderer\PhpRenderer;

/**
 * Interface for media handlers.
 *
 * Each handler corresponds to one media type.
 */
interface HandlerInterface
{
    /**
     * Get a human-readable label for the media type.
     *
     * @return string
     */
    public function getLabel();

    /**
     * Process an ingest (create) request.
     *
     * @param Media $media
     * @param Request $request
     * @param ErrorStore $errorStore
     */
    public function ingest(Media $media, Request $request, ErrorStore $errorStore);

    /**
     * Render a form for adding media.
     *
     * @param PhpRenderer $view
     * @param array $options
     * @return string
     */
    public function form(PhpRenderer $view, array $options = array());

    /**
     * Render the provided media.
     *
     * @param PhpRenderer $view
     * @param MediaRepresentation $media
     * @param array $options
     * @return string
     */
    public function render(PhpRenderer $view, MediaRepresentation $media, array $options = array());
}
