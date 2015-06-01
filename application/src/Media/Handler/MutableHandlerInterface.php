<?php
namespace Omeka\Media\Handler;

use Omeka\Api\Representation\MediaRepresentation;
use Omeka\Api\Request;
use Omeka\Entity\Media;
use Omeka\Stdlib\ErrorStore;
use Zend\View\Renderer\PhpRenderer;

/**
 * Interface for media handlers that allow updating.
 */
interface MutableHandlerInterface extends HandlerInterface
{
    /**
     * Validate a request to update media for compliance with this media type.
     *
     * @param Request $request The API request to validate
     * @param ErrorStore $errorStore
     */
    public function validateUpdateRequest(Request $request, ErrorStore $errorStore);
    
    /**
     * Process an update request.
     *
     * @param Media $media
     * @param Request $request
     * @param ErrorStore $errorStore
     */
    public function update(Media $media, Request $request, ErrorStore $errorStore);

    /**
     * Render a form for updating media.
     *
     * @param PhpRenderer $view
     * @param MediaRepresentation $media
     * @param array $options
     * @return string
     */
    public function updateForm(PhpRenderer $view, MediaRepresentation $media, array $options = array());
}
