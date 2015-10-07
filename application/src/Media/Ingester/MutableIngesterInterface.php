<?php
namespace Omeka\Media\Ingester;

use Omeka\Api\Representation\MediaRepresentation;
use Omeka\Api\Request;
use Omeka\Entity\Media;
use Omeka\Stdlib\ErrorStore;
use Zend\View\Renderer\PhpRenderer;

/**
 * Interface for media ingesters that allow updating.
 */
interface MutableIngesterInterface extends IngesterInterface
{
    /**
     * Process an update request.
     *
     * @param Media $media
     * @param Request $request
     * @param ErrorStore $errorStore
     */
    public function update(Media $media, Request $request,
        ErrorStore $errorStore);

    /**
     * Render a form for updating media.
     *
     * @param PhpRenderer $view
     * @param MediaRepresentation $media
     * @param array $options
     * @return string
     */
    public function updateForm(PhpRenderer $view, MediaRepresentation $media,
        array $options = []);
}
