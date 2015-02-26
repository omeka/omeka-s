<?php
namespace Omeka\Media\Renderer;

use Omeka\Api\Representation\Entity\MediaRepresentation;
use Zend\View\Renderer\PhpRenderer;

interface RendererInterface
{
    /**
     * Return the HTML necessary to render an add/edit form.
     *
     * @param PhpRenderer $view
     * @param MediaRepresentation|null $media
     * @param array $options
     * @return string
     */
    public function form(PhpRenderer $view, MediaRepresentation $media = null, array $options = array());

    /**
     * Return the HTML necessary to render the provided media.
     *
     * @param PhpRenderer $view
     * @param MediaRepresentation $media
     * @param array $options
     * @return string
     */
    public function render(PhpRenderer $view, MediaRepresentation $media, array $options = array());
}
