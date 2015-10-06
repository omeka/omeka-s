<?php
namespace Omeka\Media\FileRenderer;

use Omeka\Api\Representation\MediaRepresentation;
use Zend\View\Renderer\PhpRenderer;

/**
 * Interface for file renderers.
 */
interface RendererInterface
{
    /**
     * Return the HTML necessary to render the provided file.
     *
     * @param PhpRenderer $view
     * @param MediaRepresentation $media
     * @param array $options
     * @return string
     */
    public function render(PhpRenderer $view, MediaRepresentation $media,
        array $options = []);
}
